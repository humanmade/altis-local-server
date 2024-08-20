<?php

namespace Altis\Local_Server\Composer;

/**
 * Local Server docker-compose extension.
 *
 * This interface allows modules to alter the Local Server configuration for
 * Docker Compose, such as adding additional sidecar containers.
 */
interface Compose_Extension {
	/**
	 * Configure the extension.
	 *
	 * @param Docker_Compose_Generator $generator The root generator.
	 * @param array $args An optional array of arguments to modify the behaviour of the generator.
	 */
	public function set_config( Docker_Compose_Generator $generator, array $args ) : void;

	/**
	 * Filter the docker-compose.yml config.
	 *
	 * This method is supplied with the full configuration for docker-compose
	 * before it is saved to a file. Handlers can filter this value and return
	 * an updated config, such as adding additional services.
	 *
	 * @param array $config Full docker-compose.yml configuration.
	 * @return array Altered docker-compose.yml configuration.
	 */
	public function filter_compose( array $config ) : array;
}
