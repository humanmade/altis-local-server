<?php

namespace Altis\Local_Server; // @codingStandardsIgnoreLine

use function Altis\get_environment_architecture;
use function Altis\register_module;

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => get_environment_architecture() === 'local-server',
		's3' => true,
		'tachyon' => true,
		'analytics' => true,
	];

	register_module( 'local-server', __DIR__, 'Local Server', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );
