<?php

namespace HM\Platform\LocalServer\Composer;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\ArrayInput;

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
		} elseif ( $subcommand === 'cli' ) {
			return $this->cli( $input, $output );
		} elseif ( $subcommand === 'status' ) {
			return $this->status( $input, $output );
		} elseif ( $subcommand === 'logs' ) {
			return $this->logs( $input, $output );
		}
	}

	protected function start( InputInterface $input, OutputInterface $output ) {
		$output->writeln( 'Starting...' );

		$proxy = new Process( 'docker-compose -f proxy.yml up -d', 'vendor/humanmade/local-server/docker' );
		$proxy->run();

		$compose = new Process( 'docker-compose up -d', 'vendor/humanmade/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => basename( getcwd() ),
			'PATH' => getenv( 'PATH' ),
		] );
		$compose->setTimeout( 0 );
		$compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

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

		$site_url = 'https://' . basename( getcwd() ) . '.altis.dev/';
		$output->writeln( 'Startup completed.' );
		$output->writeln( 'To access your site visit: ' . $site_url );
	}

	protected function stop( InputInterface $input, OutputInterface $output ) {
		$output->writeln( 'Stopping...' );

		$proxy = new Process( 'docker-compose down', 'vendor/humanmade/local-server/docker' );
		$proxy->run();

		$compose = new Process( 'docker-compose down', 'vendor/humanmade/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => basename( getcwd() ),
		] );
		$compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );

		$output->writeln( 'Stopped...' );
	}

	protected function cli( InputInterface $input, OutputInterface $output ) {
		$site_url = 'https://' . basename( getcwd() ) . '.altis.dev/';
		$options = $input->getArgument( 'options' );
		$options[] = '--url=' . $site_url;

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

		passthru( sprintf(
			'cd %s; VOLUME=%s COMPOSE_PROJECT_NAME=%s docker-compose exec %s -u nobody php wp %s',
			'vendor/humanmade/local-server/docker',
			getcwd(),
			basename( getcwd() ),
			! posix_isatty( STDOUT ) ? '-T' : '', // forward wp-cli's isPiped detection
			implode( ' ', $options )
		), $return_val );

		return $return_val;
	}

	protected function status( InputInterface $input, OutputInterface $output ) {
		$compose = new Process( 'docker-compose ps', 'vendor/humanmade/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => basename( getcwd() ),
		] );
		$compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );
	}

	protected function logs( InputInterface $input, OutputInterface $output ) {
		$log = $input->getArgument( 'options' )[0];
		$compose = new Process( 'docker-compose logs -f ' . $log , 'vendor/humanmade/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => basename( getcwd() ),
		] );
		$compose->run( function ( $type, $buffer ) {
			echo $buffer;
		} );
	}
}
