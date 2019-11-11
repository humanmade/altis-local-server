<?php
/**
 * Local Server Composer Command.
 */

namespace Altis\LocalServer\Composer;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Command extends BaseCommand {
	protected function configure() {
		$this->setName( 'local-server' )
			->setDescription( 'Local server.' )
			->setDefinition( [
				new InputArgument( 'subcommand', null, 'start, stop, cli, status. logs.' ),
				new InputArgument( 'options', InputArgument::IS_ARRAY ),
			] )
			->setHelp(
				<<<EOT
Run the local development server.

To start the local development server:
	start
Stop the local development server:
	stop
Destroy the local development server:
	destroy
View status of the local development server:
	status
Run WP CLI command:
	cli -- <command>              eg: cli -- post list --debug
View the logs
	logs <service>                <service> can be php, nginx, db, s3, elasticsearch, xray
EOT
			);
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
			return $this->cli( $input, $output );
		} elseif ( $subcommand === 'status' ) {
			return $this->status( $input, $output );
		} elseif ( $subcommand === 'logs' ) {
			return $this->logs( $input, $output );
		} elseif ( $subcommand === 'shell' ) {
			return $this->shell( $input, $output );
		}
	}

	protected function start( InputInterface $input, OutputInterface $output ) {
		$output->writeln( 'Starting...' );

		$proxy = new Process( 'docker-compose -f proxy.yml up -d', 'vendor/altis/local-server/docker' );
		$proxy->run();

		$compose = new Process( 'docker-compose up -d', 'vendor/altis/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
			'PATH' => getenv( 'PATH' ),
		] );
		$compose->setTimeout( 0 );
		$failed = $compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		if ( $failed ) {
			return;
		}

		$cli = $this->getApplication()->find( 'local-server' );

		// Check if WP is already installed.
		$is_installed = $cli->run( new ArrayInput( [
			'subcommand' => 'cli',
			'options' => [
				'core',
				'is-installed',
			],
		] ), $output ) === 0;

		if ( ! $is_installed ) {
			$cli->run( new ArrayInput( [
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
				],

			] ), $output ) === 0;
			$output->writeln( 'Installed database.' );
		}

		$site_url = 'https://' . $this->get_project_subdomain() . '.altis.dev/';
		$output->writeln( 'Startup completed.' );
		$output->writeln( 'To access your site visit: ' . $site_url );
	}

	protected function stop( InputInterface $input, OutputInterface $output ) {
		$output->writeln( 'Stopping...' );

		$proxy = new Process( 'docker-compose stop', 'vendor/altis/local-server/docker' );
		$proxy->run();

		$compose = new Process( 'docker-compose stop', 'vendor/altis/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
		] );
		$compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		$output->writeln( 'Stopped.' );
	}

	protected function destroy( InputInterface $input, OutputInterface $output ) {
		$output->writeln( 'Destroying...' );

		$proxy = new Process( 'docker-compose down -v', 'vendor/altis/local-server/docker' );
		$proxy->run();

		$compose = new Process( 'docker-compose down -v', 'vendor/altis/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => $this->get_project_subdomain(),
		] );
		$compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		$output->writeln( 'Destroyed.' );
	}

	protected function restart( InputInterface $input, OutputInterface $output ) {
		$this->stop( $input, $output );
		$this->start( $input, $output );
	}

	protected function cli( InputInterface $input, OutputInterface $output ) {
		$site_url = 'https://' . $this->get_project_subdomain() . '.altis.dev/';
		$options = $input->getArgument( 'options' );

		$passed_url = false;
		foreach ( $options as $option ) {
			if ( strpos( $option, '--url=' ) === 0 ) {
				$passed_url = true;
				break;
			}
		}

		if ( ! $passed_url ) {
			$options[] = '--url=' . $site_url;
		}

		// Escape all options. Because the shell is going to strip the
		// initial escaping like "My string" => My String, then we need
		// to reapply escaping.
		foreach ( $options as &$option ) {
			if ( ! strpos( $option, '=' ) ) {
				if ( strpos( $option, '--' ) == 0 ) {
					continue;
				}
				$option = escapeshellarg( $option );
			} else {
				$arg = strtok( $option, '=' );
				$option = $arg . '=' . escapeshellarg( substr( $option, strlen( $arg ) + 1 ) );
			}
		}
		$columns = exec( 'tput cols' );
		$lines = exec( 'tput lines' );
		$has_stdin = ! posix_isatty( STDIN );
		$command = sprintf(
			'cd %s; VOLUME=%s COMPOSE_PROJECT_NAME=%s docker-compose exec -e COLUMNS=%d -e LINES=%d %s -u nobody php wp %s',
			'vendor/altis/local-server/docker',
			getcwd(),
			$this->get_project_subdomain(),
			$columns,
			$lines,
			$has_stdin || ! posix_isatty( STDOUT ) ? '-T' : '', // forward wp-cli's isPiped detection
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
		$compose->run( function ( $type, $buffer ) {
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
		$compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );
	}

	protected function shell( InputInterface $input, OutputInterface $output ) {
		passthru( sprintf(
			'cd %s; VOLUME=%s COMPOSE_PROJECT_NAME=%s docker-compose exec php /bin/bash',
			'vendor/altis/local-server/docker',
			getcwd(),
			$this->get_project_subdomain()
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
