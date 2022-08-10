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

	if ( $config['s3'] ?? true ) {
		define( 'S3_UPLOADS_BUCKET', getenv( 'S3_UPLOADS_BUCKET' ) );
		define( 'S3_UPLOADS_REGION', getenv( 'S3_UPLOADS_REGION' ) );
		define( 'S3_UPLOADS_KEY', getenv( 'S3_UPLOADS_KEY' ) );
		define( 'S3_UPLOADS_SECRET', getenv( 'S3_UPLOADS_SECRET' ) );
		define( 'S3_UPLOADS_ENDPOINT', getenv( 'S3_UPLOADS_ENDPOINT' ) );
		define( 'S3_UPLOADS_BUCKET_URL', getenv( 'S3_UPLOADS_BUCKET_URL' ) );

		add_filter( 's3_uploads_s3_client_params', function ( $params ) {
			if ( defined( 'S3_UPLOADS_ENDPOINT' ) && S3_UPLOADS_ENDPOINT ) {
				$params['endpoint'] = S3_UPLOADS_ENDPOINT;
				$params['bucket_endpoint'] = true;
				$params['http'] = [
					'verify' => false,
				];
			}
			return $params;
		}, 5, 1 );
	}

	if ( empty( $_SERVER['HTTP_HOST'] ) ) {
		$_SERVER['HTTP_HOST'] = getenv( 'COMPOSE_PROJECT_NAME' ) . '.' . getenv( 'COMPOSE_PROJECT_TLD' );
	}

	defined( 'DB_HOST' ) or define( 'DB_HOST', getenv( 'DB_HOST' ) );
	defined( 'DB_USER' ) or define( 'DB_USER', getenv( 'DB_USER' ) );
	defined( 'DB_PASSWORD' ) or define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) );
	defined( 'DB_NAME' ) or define( 'DB_NAME', getenv( 'DB_NAME' ) );

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

	if ( $config['tachyon'] ?? true ) {
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

	if ( $config['analytics'] ?? true ) {
		define( 'ALTIS_ANALYTICS_PINPOINT_ID', '12345678901234567890123456' );
		define( 'ALTIS_ANALYTICS_PINPOINT_REGION', 'us-east-1' );
		define( 'ALTIS_ANALYTICS_COGNITO_ID', 'us-east-1:f6f6f6-fafa-f5f5-8f8f-1234567890' );
		define( 'ALTIS_ANALYTICS_COGNITO_REGION', 'us-east-1' );
		define( 'ALTIS_ANALYTICS_PINPOINT_ENDPOINT', getenv( 'ALTIS_ANALYTICS_PINPOINT_ENDPOINT' ) );
		define( 'ALTIS_ANALYTICS_COGNITO_ENDPOINT', getenv( 'ALTIS_ANALYTICS_COGNITO_ENDPOINT' ) );
	}

	add_filter( 'qm/output/file_path_map', __NAMESPACE__ . '\\set_file_path_map', 1 );

	// Filter ES package IDs for local.
	add_filter( 'altis.search.packages_dir', __NAMESPACE__ . '\\set_search_packages_dir' );
	add_filter( 'altis.search.create_package_id', __NAMESPACE__ . '\\set_search_package_id', 10, 3 );

	// If we're on Codespaces, the native host will be localhost.
	if ( $config['codespaces_integration'] ?? null && $_SERVER['HTTP_HOST'] === 'localhost' ) {
		// Use forwarded host if we can.
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) {
			// phpcs:ignore HM.Security.ValidatedSanitizedInput
			$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
		}
	}

	// Disable HTTPS validation for local URLs.
	add_filter( 'https_ssl_verify', __NAMESPACE__ . '\\disable_self_ssl_verification', 10, 2 );

	// Route e-mails to MailHog.
	add_action( 'phpmailer_init', __NAMESPACE__ . '\\mailhog_init' );
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
			'url' => getenv( 'S3_CONSOLE_URL' ),
		],
	];

	foreach ( $links as $link ) {
		add_management_page( $link['label'], $link['label'], 'manage_options', $link['url'] );
	}
}

/**
 * Override Elasticsearch package storage location to es-packages volume.
 *
 * This directory is shared with the Elasticsearch container.
 *
 * @return string
 */
function set_search_packages_dir() : string {
	return sprintf( 's3://%s/uploads/es-packages', S3_UPLOADS_BUCKET );
}

/**
 * Override the derived ES package file name for local server.
 *
 * @param string|null $id The package ID used for the file path in ES.
 * @param string $slug The package slug.
 * @param string $file The package file path on S3.
 * @return string|null
 */
function set_search_package_id( $id, string $slug, string $file ) : ?string {
	$id = sprintf( 'packages/%s', basename( $file ) );
	return $id;
}

/**
 * Disables SSL verification for local HTTP calls.
 *
 * @param boolean $verify Whether to verify SSL certificates for local calls.
 * @param string $url URL being requested.
 *
 * @return boolean
 */
function disable_self_ssl_verification( bool $verify, string $url ) : bool {
	$domains = get_config_domains( true );
	$request_domain = parse_url( $url, PHP_URL_HOST );

	if (
		in_array( $request_domain, $domains, true )
		||
		false !== strpos( $request_domain, $domains[0] ) // Support subdomains of primary domain.
	) {
		$verify = false;
	}

	return $verify;
}

/**
 * Get domains configured in Altis config.
 *
 * @param bool $include_aux_services Add domains for auxiliary services like S3.
 *
 * @return array
 */
function get_config_domains( bool $include_aux_services = false ) : array {
	static $domains = [];

	if ( ! $domains ) {
		$config = Altis\get_config()['modules']['local-server'];

		$project_name = preg_replace( '/[^A-Za-z0-9\-\_]/', '', $config['name'] ?? basename( getcwd() ) );
		$project_tld = $config['tld'] ?? 'altis.dev';
		$domains[] = $project_name . '.' . $project_tld;

		$domains = array_merge( $domains, $config['domains'] ?? [] );
		$domains[] = 'altis.dev';
	}

	if ( $include_aux_services ) {
		$aux_services = [
			'elasticsearch',
			'pinpoint',
			'cognito',
			's3',
			's3-console',
		];

		foreach ( $aux_services as $aux_service ) {
			$domains[] = "{$aux_service}-{$domains[0]}";
		}
	}

	return $domains;
}

/**
 * Configure phpMailer to use MailHog with Local Server.
 *
 * @param PHPMailer $phpmailer The WordPress PHPMailer instance.
 */
function mailhog_init( $phpmailer ) {
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$phpmailer->isSMTP();
	$phpmailer->Host     = 'mailhog'; 
	$phpmailer->SMTPAuth = false;
	$phpmailer->Port     = 1025;
	// phpcs:enable
}
