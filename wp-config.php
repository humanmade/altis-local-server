<?php

if ( empty( $_SERVER['HTTP_HOST'] ) ) {
	$_SERVER['HTTP_HOST'] = getenv( 'COMPOSE_PROJECT_NAME' ) . '.hmdocker';
}

define( 'DB_HOST', getenv( 'DB_HOST' ) );
define( 'DB_USER', getenv( 'DB_USER' ) );
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) );
define( 'DB_NAME', getenv( 'DB_NAME' ) );

define( 'ELASTICSEARCH_HOST', getenv( 'ELASTICSEARCH_HOST' ) );
define( 'ELASTICSEARCH_PORT', getenv( 'ELASTICSEARCH_PORT' ) );

define( 'S3_UPLOADS_BUCKET', getenv( 'S3_UPLOADS_BUCKET' ) );
define( 'S3_UPLOADS_REGION', getenv( 'S3_UPLOADS_REGION' ) );
define( 'S3_UPLOADS_KEY', getenv( 'S3_UPLOADS_KEY' ) );
define( 'S3_UPLOADS_SECRET', getenv( 'S3_UPLOADS_SECRET' ) );
define( 'S3_UPLOADS_ENDPOINT', getenv( 'S3_UPLOADS_ENDPOINT' ) );
define( 'S3_UPLOADS_BUCKET_URL', getenv( 'S3_UPLOADS_BUCKET_URL' ) );

define( 'TACHYON_URL', getenv( 'TACHYON_URL' ) );

global $redis_server;
$redis_server = [
	'host' => getenv( 'REDIS_HOST' ),
	'port' => getenv( 'REDIS_PORT' ),
];

ini_set( 'display_errors', 'on' );

add_filter( 's3_uploads_s3_client_params', function ( $params ) {
	if ( defined( 'S3_UPLOADS_ENDPOINT' ) ) {
		$params['endpoint'] = S3_UPLOADS_ENDPOINT;
	}
	return $params;
}, 5, 1 );
