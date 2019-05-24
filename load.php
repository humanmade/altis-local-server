<?php

namespace Altis\Local_Server; // @codingStandardsIgnoreLine

use function Altis\get_environment_architecture;
use function Altis\register_module;

require_once __DIR__ . '/inc/namespace.php';

// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => get_environment_architecture() === 'local-server',
		's3'      => true,
		'tachyon' => true,
	];

	register_module( 'local-server', __DIR__, 'Local Server', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );
