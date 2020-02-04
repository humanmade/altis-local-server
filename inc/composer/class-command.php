<?php
/**
 * Local Server Composer Command.
 */

namespace Altis\Local_Server\Composer;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Command extends BaseCommand {
	protected function configure() {
		$this
			->setName( 'server' )
			->setDescription( 'Altis Local Server' )
			->setDefinition( [
				new InputArgument( 'subcommand', null, 'start, stop, restart, cli, exec. shell, status. logs.' ),
				new InputArgument( 'options', InputArgument::IS_ARRAY ),
			] )
			->setAliases( [ 'local-server' ] )
			->setHelp(
				<<<EOT
Run the local development server.

Default command - start the local development server:
	start [--xdebug]              passing --xdebug starts the server with xdebug enabled
Stop the local development server:
	stop
Restart the local development server:
	restart [--xdebug]            passing --xdebug restarts the server with xdebug enabled
Destroy the local development server:
	destroy
View status of the local development server:
	status
Run WP CLI command:
	cli -- <command>              eg: cli -- post list --debug
Run any shell command from the PHP container:
	exec -- <command>             eg: exec -- vendor/bin/phpcs
Open a shell:
	shell
View the logs
	logs <service>                <service> can be php, nginx, db, s3, elasticsearch, xray
EOT
			)
			->addOption( 'xdebug' );
	}

	public function isProxyCommand() {
		return true;
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$subcommand = $input->getArgument( 'subcommand' );

		if ( $subcommand === 'start' ) {
			return $this->start( $input, $output );
		} elseif ( $subcommand === 'stop' ) {
			return $this->stop( $input, $output );
		} elseif ( $subcommand === 'restart' ) {
			return $this->restart( $input, $output );
		} elseif ( $subcommand === 'destroy' ) {
			return $this->destroy( $input, $output );
		} elseif ( $subcommand === 'cli' ) {
			return $this->exec( $input, $output, 'wp' );
		} elseif ( $subcommand === 'exec' ) {
			return $this->exec( $input, $output );
		} elseif ( $subcommand === 'status' ) {
			return $this->status( $input, $output );
		} elseif ( $subcommand === 'logs' ) {
			return $this->logs( $input, $output );
		} elseif ( $subcommand === 'shell' ) {
			return $this->shell( $input, $output );
		} elseif ( $subcommand === null ) {
			// Default to start command.
			return $this->start( $input, $output );
		}

		$output->writeln( '<error>' . $subcommand . ' command not found.</>' );
		return 1;
	}

	protected function start( InputInterface $input, OutputInterface $output ) {
		$output->writeln( '<info>Starting...</>' );

		$proxy = new Process( 'docker-compose -f proxy.yml up -d', 'vendor/altis/local-server/docker' );
		$proxy->setTimeout( 0 );
		$proxy->setTty( true );
		$proxy_failed = $proxy->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		if ( $proxy_failed ) {
			$output->writeln( '<error>Could not start traefik proxy.</>' );
			return $proxy_failed;
		}

		$env = [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
			'PATH' => getenv( 'PATH' ),
			'ES_MEM_LIMIT' => getenv( 'ES_MEM_LIMIT' ) ?: '1g',
		];

		if ( $input->getOption( 'xdebug' ) ) {
			$env['PHP_IMAGE'] = 'humanmade/altis-local-server-php:3.1.0-dev';
			$env['PHP_XDEBUG_ENABLED'] = true;
		}

		$compose = new Process( 'docker-compose up -d', 'vendor/altis/local-server/docker', $env );
		$compose->setTty( true );
		$compose->setTimeout( 0 );
		$failed = $compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		if ( $failed ) {
			$output->writeln( '<error>Services failed to start successfully.</>' );
			return $failed;
		}

		$cli = $this->getApplication()->find( 'local-server' );

		// Check if WP is already installed.
		$is_installed = $cli->run( new ArrayInput( [
			'subcommand' => 'cli',
			'options' => [
				'core',
				'is-installed',
				'--quiet',
			],
		] ), $output ) === 0;

		if ( ! $is_installed ) {
			$install_failed = $cli->run( new ArrayInput( [
				'subcommand' => 'cli',
				'options' => [
					'core',
					'multisite-install',
					'--title=Altis',
					'--admin_user=admin',
					'--admin_password=admin',
					'--admin_email=no-reply@altis.dev',
					'--skip-email',
					'--skip-config',
					'--quiet',
				],
			] ), $output );

			// Check install was successful.
			if ( $install_failed ) {
				$output->writeln( sprintf( '<error>WordPress install failed. Exited with error code %d</>', $install_failed ) );
				return $install_failed;
			}

			$output->writeln( '<info>Installed database.</>' );
			$output->writeln( '<info>WP Username:</>	<comment>admin</>' );
			$output->writeln( '<info>WP Password:</>	<comment>admin</>' );
		}

		// Ensure uploads directory is present by copying a known file.
		// Prevents errors when running WordPress unit tests.
		$cli->run( new ArrayInput( [
			'subcommand' => 'cli',
			'options' => [
				's3-uploads',
				'cp',
				'composer.json',
				's3://s3-' . $this->get_project_subdomain() . '/uploads/composer.json',
				'--quiet',
			],
		] ), $output );
		$cli->run( new ArrayInput( [
			'subcommand' => 'cli',
			'options' => [
				's3-uploads',
				'rm',
				's3://s3-' . $this->get_project_subdomain() . '/uploads/composer.json',
				'--quiet',
			],
		] ), $output );

		$site_url = 'https://' . $this->get_project_subdomain() . '.altis.dev/';
		$output->writeln( '<info>Startup completed.</>' );
		$output->writeln( '<info>To access your site visit:</> <comment>' . $site_url . '</>' );

	}

	protected function stop( InputInterface $input, OutputInterface $output ) {
		$output->writeln( '<info>Stopping...</>' );

		$proxy = new Process( 'docker-compose stop', 'vendor/altis/local-server/docker' );
		$proxy->run();

		$compose = new Process( 'docker-compose stop', 'vendor/altis/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
		] );
		$return_val = $compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		if ( $return_val === 0 ) {
			$output->writeln( '<info>Stopped.</>' );
		} else {
			$output->writeln( '<error>Failed to stop services.</>' );
		}

		return $return_val;
	}

	protected function destroy( InputInterface $input, OutputInterface $output ) {
		$output->writeln( '<error>Destroying...</>' );

		$proxy = new Process( 'docker-compose down -v', 'vendor/altis/local-server/docker' );
		$proxy->run();

		$compose = new Process( 'docker-compose down -v', 'vendor/altis/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
		] );
		$return_val = $compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		if ( $return_val === 0 ) {
			$output->writeln( '<error>Destroyed.</>' );
		} else {
			$output->writeln( '<error>Failed to destroy services.</>' );
		}

		return $return_val;
	}

	protected function restart( InputInterface $input, OutputInterface $output ) {
		$this->stop( $input, $output );
		return $this->start( $input, $output );
	}

	protected function exec( InputInterface $input, OutputInterface $output, ?string $program = null ) {
		$site_url = 'https://' . $this->get_project_subdomain() . '.altis.dev/';
		$options = $input->getArgument( 'options' );

		$passed_url = false;
		foreach ( $options as $option ) {
			if ( strpos( $option, '--url=' ) === 0 ) {
				$passed_url = true;
				break;
			}
		}

		if ( ! $passed_url && $program === 'wp' ) {
			$options[] = '--url=' . $site_url;
		}

		// Escape all options. Because the shell is going to strip the
		// initial escaping like "My string" => My String, then we need
		// to reapply escaping.
		foreach ( $options as &$option ) {
			$is_unescaped_string = preg_match( "/\\'/", $option ) !== false;
			$double_dash_arg_position = strpos( $option, '--' );
			$equal_arg_position = strpos( $option, '=' );

			if ( $is_unescaped_string && $double_dash_arg_position !== 0 && $equal_arg_position !== 0 ) {
				// It's an string without escaping and it doesn't start with = or --
				$option = escapeshellarg( $option );
			} elseif ( ! $equal_arg_position ) {
				// The option starts with = or there isn't an = at all.
				if ( $double_dash_arg_position == 0 ) {
					// The option starts with double dash or there isn't a -- at all.
					continue;
				}
				$option = escapeshellarg( $option );
			} else {
				$arg = strtok( $option, '=' );
				$option = $arg . '=' . escapeshellarg( substr( $option, strlen( $arg ) + 1 ) );
			}
		}

		$container_id = exec( sprintf( 'docker ps --filter name=%s_php_1 -q', $this->get_project_subdomain() ) );
		if ( ! $container_id ) {
			$output->writeln( '<error>PHP container not found to run command.</>' );
			return 1;
		}

		$columns = exec( 'tput cols' );
		$lines = exec( 'tput lines' );
		$has_stdin = ! posix_isatty( STDIN );
		$command = sprintf(
			'docker exec -e COLUMNS=%d -e LINES=%d -u nobody %s %s %s %s',
			$columns,
			$lines,
			( $has_stdin || ! posix_isatty( STDOUT ) ) && $program === 'wp' ? '-t' : '', // forward wp-cli's isPiped detection
			$container_id,
			$program ?? '',
			implode( ' ', $options )
		);

		passthru( $command, $return_val );

		return $return_val;
	}

	protected function status( InputInterface $input, OutputInterface $output ) {
		$compose = new Process( 'docker-compose ps', 'vendor/altis/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
		] );
		return $compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );
	}

	protected function logs( InputInterface $input, OutputInterface $output ) {
		$log = $input->getArgument( 'options' )[0];
		$compose = new Process( 'docker-compose logs --tail=100 -f ' . $log, 'vendor/altis/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
		] );
		$compose->setTimeout( 0 );
		return $compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );
	}

	protected function shell( InputInterface $input, OutputInterface $output ) {
		$columns = exec( 'tput cols' );
		$lines = exec( 'tput lines' );
		passthru( sprintf(
			'cd %s; VOLUME=%s COMPOSE_PROJECT_NAME=%s docker-compose exec -e COLUMNS=%d -e LINES=%d php /bin/bash',
			'vendor/altis/local-server/docker',
			escapeshellarg( getcwd() ),
			$this->get_project_subdomain(),
			$columns,
			$lines
		), $return_val );

		return $return_val;
	}

	/**
	 * Get the name of the project for the local subdomain
	 *
	 * @return string
	 */
	protected function get_project_subdomain() : string {

		$composer_json = json_decode( file_get_contents( getcwd() . '/composer.json' ), true );

		if ( isset( $composer_json['extra']['altis']['modules']['local-server']['name'] ) ) {
			$project_name = $composer_json['extra']['altis']['modules']['local-server']['name'];
		} else {
			$project_name = basename( getcwd() );
		}

		return preg_replace( '/[^A-Za-z0-9\-\_]/', '', $project_name );
	}
}
