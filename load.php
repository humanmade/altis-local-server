<?php
/**
 * Altis Local Server Module.
 *
 * @package altis/local-server
 */

namespace Altis\Local_Server; // phpcs:ignore

use Altis;

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => Altis\get_environment_architecture() === 'local-server',
		'php' => '7.4',
	];
	$options = [
		'defaults' => $default_settings,
	];
	Altis\register_module( 'local-server', __DIR__, 'Local Server', $options, __NAMESPACE__ . '\\bootstrap' );
} );
