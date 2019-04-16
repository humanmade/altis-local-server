<?php

namespace HM\Platform\Local_Server;

use function HM\Platform\get_config;
use function HM\Platform\get_environment_architecture;
use function HM\Platform\register_module;

// Don't self-initialize if this is not a Platform execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'hm-platform.modules.init', function () {
	$default_settings = [
		'enabled' => get_environment_architecture() === 'local-server',
		's3'      => true,
		'tachyon' => true,
	];

	register_module( 'local-server', __DIR__, 'Local Server', $default_settings, function () {
		$config = get_config()['modules']['local-server'];

		if ( $config['s3'] ) {
			define( 'S3_UPLOADS_BUCKET', getenv( 'S3_UPLOADS_BUCKET' ) );
			define( 'S3_UPLOADS_REGION', getenv( 'S3_UPLOADS_REGION' ) );
			define( 'S3_UPLOADS_KEY', getenv( 'S3_UPLOADS_KEY' ) );
			define( 'S3_UPLOADS_SECRET', getenv( 'S3_UPLOADS_SECRET' ) );
			define( 'S3_UPLOADS_ENDPOINT', getenv( 'S3_UPLOADS_ENDPOINT' ) );
			define( 'S3_UPLOADS_BUCKET_URL', getenv( 'S3_UPLOADS_BUCKET_URL' ) );

			add_filter( 's3_uploads_s3_client_params', function ( $params ) {
				if ( defined( 'S3_UPLOADS_ENDPOINT' ) ) {
					$params['endpoint'] = S3_UPLOADS_ENDPOINT;
				}
				return $params;
			}, 5, 1 );
		}


		if ( empty( $_SERVER['HTTP_HOST'] ) ) {
			$_SERVER['HTTP_HOST'] = getenv( 'COMPOSE_PROJECT_NAME' ) . '.altis.dev';
		}

		define( 'DB_HOST', getenv( 'DB_HOST' ) );
		define( 'DB_USER', getenv( 'DB_USER' ) );
		define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) );
		define( 'DB_NAME', getenv( 'DB_NAME' ) );

		define( 'ELASTICSEARCH_HOST', getenv( 'ELASTICSEARCH_HOST' ) );
		define( 'ELASTICSEARCH_PORT', getenv( 'ELASTICSEARCH_PORT' ) );

		define( 'AWS_XRAY_DAEMON_IP_ADDRESS', gethostbyname( getenv( 'AWS_XRAY_DAEMON_HOST' ) ) );

		global $redis_server;
		$redis_server = [
			'host' => getenv( 'REDIS_HOST' ),
			'port' => getenv( 'REDIS_PORT' ),
		];

		ini_set( 'display_errors', 'on' );

		if ( $config['tachyon'] ) {
			define( 'TACHYON_URL', getenv( 'TACHYON_URL' ) );

			/**
			 * In local-server, the tachyon hostname resolves to what is deemed a local url.
			 * This makes requests to tachyon from WordPress disallowed. We want to
			 * specifically allow that host.
			 */
			add_filter( 'http_request_host_is_external', function ( bool $is_external, string $host ) : bool {
				if ( $is_external ) {
					return $is_external;
				}

				return parse_url( TACHYON_URL, PHP_URL_HOST ) === $host;
			}, 10, 2 );
		}
	} );
} );
