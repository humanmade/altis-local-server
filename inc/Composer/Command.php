<?php

namespace HM\Platform\LocalServer\Composer;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Command extends BaseCommand {
	protected function configure() {
		$this->setName( 'local-server' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$output->writeln( 'Starting...' );
		if ( ! file_exists( '/etc/resolver/hmdocker' ) ) {
			`sh vendor/humanmade/local-server/docker/bin/install.sh`;
		}

		$proxy = new Process( 'docker-compose -f proxy.yml up', 'vendor/humanmade/local-server/docker' );
		$proxy->start();

		$compose = new Process( 'docker-compose up', 'vendor/humanmade/local-server/docker', [
			'VOLUME' => getcwd(),
			'COMPOSE_PROJECT_NAME' => basename( getcwd() ),
		] );

		$compose->start( function ( $type, $buffer ) {
			echo $buffer;
		} );

		$compose->wait();

		//`NAME=$(basename "\$PWD") ; cd vendor/humanmade/local-server/docker && docker-compose -f proxy.yml up -d && VOLUME=../../../../ COMPOSE_PROJECT_NAME=\$NAME docker-compose up && echo "Started." && open "http://\$NAME.hmdocker"`;
	}
}
