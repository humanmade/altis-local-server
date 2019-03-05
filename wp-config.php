<?php

if ( empty( $_SERVER['HTTP_HOST'] ) ) {
	$_SERVER['HTTP_HOST'] = getenv( 'COMPOSE_PROJECT_NAME' ) . '.hmdocker';
}

define( 'DB_HOST', getenv( 'DB_HOST' ) );
define( 'DB_USER', getenv( 'DB_USER' ) );
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) );
define( 'DB_NAME', getenv( 'DB_NAME' ) );

global $redis_server;
$redis_server = [
	'host' => getenv( 'REDIS_HOST' ),
	'port' => getenv( 'REDIS_PORT' ),
];
