<?php
/**
 * Altis Local Server.
 *
 * @package altis/local-server
 */

namespace Altis\Local_Server;

use Altis;

/**
 * Configure environment for local server.
 */
function bootstrap() {
	add_filter( 'admin_menu', __NAMESPACE__ . '\\tools_submenus' );

	$config = Altis\get_config()['modules']['local-server'];

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

	if ( ! defined( 'AWS_XRAY_DAEMON_IP_ADDRESS' ) ) {
		define( 'AWS_XRAY_DAEMON_IP_ADDRESS', gethostbyname( getenv( 'AWS_XRAY_DAEMON_HOST' ) ) );
	}

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

			// @codingStandardsIgnoreLine
			return parse_url( TACHYON_URL, PHP_URL_HOST ) === $host;
		}, 10, 2 );
	}

	if ( $config['analytics'] ) {
		define( 'ALTIS_ANALYTICS_PINPOINT_ID', '12345678901234567890123456' );
		define( 'ALTIS_ANALYTICS_PINPOINT_REGION', 'us-east-1' );
		define( 'ALTIS_ANALYTICS_COGNITO_ID', 'us-east-1:f6f6f6-fafa-f5f5-8f8f-1234567890' );
		define( 'ALTIS_ANALYTICS_COGNITO_REGION', 'us-east-1' );
		define( 'ALTIS_ANALYTICS_PINPOINT_ENDPOINT', getenv( 'ALTIS_ANALYTICS_PINPOINT_ENDPOINT' ) );
		define( 'ALTIS_ANALYTICS_COGNITO_ENDPOINT', getenv( 'ALTIS_ANALYTICS_COGNITO_ENDPOINT' ) );
	}

	add_filter( 'qm/output/file_path_map', __NAMESPACE__ . '\\set_file_path_map', 1 );
}

/**
 * Enables Query Monitor to map paths to their original values on the host.
 *
 * @param array $map Map of guest path => host path.
 * @return array Adjusted mapping of folders.
 */
function set_file_path_map( array $map ) : array {
	if ( ! getenv( 'HOST_PATH' ) ) {
		return $map;
	}
	$map['/usr/src/app'] = rtrim( getenv( 'HOST_PATH' ), DIRECTORY_SEPARATOR );
	return $map;
}

/**
 * Add new submenus to Tools admin menu.
 */
function tools_submenus() {
	$links = [
		[
			'label' => 'Kibana',
			'url' => network_site_url( '/kibana' ),
		],
		[
			'label' => 'MailHog',
			'url' => network_site_url( '/mailhog' ),
		],
		[
			'label' => 'S3 Browser',
			'url' => S3_UPLOADS_BUCKET_URL . '/minio',
		],
	];

	foreach ( $links as $link ) {
		add_management_page( $link['label'], $link['label'], 'manage_options', $link['url'] );
	}
}
