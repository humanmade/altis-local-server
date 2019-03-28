<?php

namespace HM\Platform\Local_Server;

use function HM\Platform\register_module;

add_action( 'hm-platform.modules.init', function () {
	$default_settings = [];
	register_module( 'local-server', __DIR__, 'Local Server', $default_settings );
} );
