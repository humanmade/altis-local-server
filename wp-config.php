<?php
/**
 * This file will always be included if local-server is installed.
 * We only want to do anything if we are actually running in the
 * local-server context. Therefore, we only define HM_ENV_ARCHITECTURE
 * if we are in that context.
 *
 * This module will only then enable it's self if the architecture is local-server.
 *
 * @package altis/local-server
 */

/* phpcs:disable PSR1.Files.SideEffects */
if ( defined( 'PHP_SAPI' ) && PHP_SAPI === 'cli' ) {
	ini_set( 'display_errors', 'on' );
}

if ( getenv( 'HM_ENV_ARCHITECTURE' ) === 'local-server' ) {
	define( 'HM_ENV_ARCHITECTURE', getenv( 'HM_ENV_ARCHITECTURE' ) );
}
/* phpcs:enable PSR1.Files.SideEffects */
