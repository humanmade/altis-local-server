<?php

namespace Altis\Local_Server; // @codingStandardsIgnoreLine

use function Altis\get_environment_architecture;
use function Altis\register_module;

<<<<<<< HEAD
// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

=======
>>>>>>> 29fd9e01bddff815c19a302faac1f3b99ea4cf42
add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => get_environment_architecture() === 'local-server',
		's3' => true,
		'tachyon' => true,
		'analytics' => true,
	];

	register_module( 'local-server', __DIR__, 'Local Server', $default_settings, __NAMESPACE__ . '\\bootstrap' );
} );
