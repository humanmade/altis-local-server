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
		's3' => true,
		'tachyon' => true,
		'analytics' => true,
	];

	Altis\register_module( 'local-server', __DIR__, 'Local Server', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );
