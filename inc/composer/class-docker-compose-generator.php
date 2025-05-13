<?php
/**
 * Local Server Docker Compose file generator.
 */

namespace Altis\Local_Server\Composer;

use Altis;
use Symfony\Component\Yaml\Yaml;

/**
 * Generates a docker compose file for Altis Local Server.
 */
class Docker_Compose_Generator {

	/**
	 * The Docker Compose project name.
	 *
	 * Commonly set via the `COMPOSE_PROJECT_NAME` environment variable.
	 *
	 * @var string
	 */
	public $project_name;

	/**
	 * The S3 bucket name.
	 *
	 * @var string
	 */
	public $bucket_name;

	/**
	 * The Altis project root directory.
	 *
	 * @var string
	 */
	public $root_dir;

	/**
	 * The docker-compose.yml directory.
	 *
	 * @var string
	 */
	public $config_dir;

	/**
	 * The primary top level domain for the server.
	 *
	 * @var string
	 */
	public $tld;

	/**
	 * The primary domain name for the project.
	 *
	 * @var string
	 */
	public $hostname;

	/**
	 * The client facing domain name for the project.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * An array of data passed to
	 *
	 * @var array
	 */
	public $args;

	/**
	 * Extra configuration from packages.
	 *
	 * @var array
	 */
	protected $extra;

	/**
	 * Create and configure the generator.
	 *
	 * @param string $root_dir The project root directory.
	 * @param string $project_name The docker compose project name.
	 * @param string $tld The primary top level domain for the server.
	 * @param string $url The client facing URL.
	 * @param array $args An optional array of arguments to modify the behaviour of the generator.
	 * @param array $extra Extra configuration from packages.
	 */
	public function __construct( string $root_dir, string $project_name, string $tld, string $url, array $args = [], array $extra = [] ) {
		$this->project_name = $project_name;
		$this->bucket_name = "s3-{$this->project_name}";
		$this->config_dir = dirname( __DIR__, 2 ) . '/docker';
		$this->root_dir = $root_dir;
		$this->tld = $tld;
		$this->hostname = $this->tld ? $this->project_name . '.' . $this->tld : $this->project_name;
		$this->url = $url;
		$this->args = $args;
		$this->extra = $extra;
	}

	/**
	 * Get the PHP server configuration.
	 *
	 * @return array
	 */
	protected function get_php_reusable() : array {
		$version_map = [
			'8.3' => 'humanmade/altis-local-server-php:8.3.14',
			'8.2' => 'humanmade/altis-local-server-php:8.2.28',
			'8.1' => 'humanmade/altis-local-server-php:6.0.25',
		];

		$versions = array_keys( $version_map );
		$version = (string) $this->get_config()['php'];

		if ( ! in_array( $version, $versions, true ) ) {
			echo sprintf(
				"The configured PHP version \"%s\" is not supported.\nTry one of the following:\n  - %s\n",
				// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
				$version,
				// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
				implode( "\n  - ", $versions )
			);
			exit( 1 );
		}

		$image = $version_map[ $version ];

		$volumes = [
			$this->get_app_volume(),
			"{$this->config_dir}/php.ini:/usr/local/etc/php/conf.d/altis.ini",
			'socket:/var/run/php-fpm',
			'tmp:/tmp',
		];

		if ( $this->args['xdebug'] !== 'off' ) {
			$volumes[] = "{$this->config_dir}/xdebug.ini:/usr/local/etc/php/conf.d/xdebug.ini";
		}

		if ( $this->get_config()['afterburner'] && $version !== '7.4' ) {
			$volumes[] = "{$this->config_dir}/afterburner.ini:/usr/local/etc/php/conf.d/afterburner.ini";
		}

		$services = [
			'init' => true,
			'depends_on' => [
				'db' => [
					'condition' => 'service_healthy',
				],
				'redis' => [
					'condition' => 'service_started',
				],
				'mailhog' => [
					'condition' => 'service_started',
				],
				's3-create-bucket' => [
					'condition' => 'service_completed_successfully',
				],
				's3-create-user' => [
					'condition' => 'service_completed_successfully',
				]
			],
			'image' => $image,
			'links' => [
				'db',
				'db:db-read-replica',
				's3',
				's3:s3.localhost',
			],
			'external_links' => [
				"proxy:{$this->hostname}",
				"proxy:pinpoint-{$this->hostname}",
				"proxy:cognito-{$this->hostname}",
				"proxy:s3-{$this->hostname}",
				"proxy:s3-{$this->project_name}.localhost",
			],
			'volumes' => $volumes,
			'networks' => [
				'proxy',
				'default',
			],
			'environment' => [
				'HOST_PATH' => $this->root_dir,
				'COMPOSE_PROJECT_NAME' => $this->hostname,
				'DB_HOST' => 'db',
				'DB_READ_REPLICA_HOST' => 'db-read-replica',
				'DB_PASSWORD' => 'wordpress',
				'DB_NAME' => 'wordpress',
				'DB_USER' => 'wordpress',
				'REDIS_HOST' => 'redis',
				'REDIS_PORT' => 6379,
				'WP_DEBUG' => 1,
				'WP_DEBUG_DISPLAY' => 0,
				'PAGER' => 'more',
				'HM_ENV_ARCHITECTURE' => 'local-server',
				'HM_DEPLOYMENT_REVISION' => 'dev',
				'AWS_XRAY_DAEMON_HOST' => 'xray',
				'S3_UPLOADS_ENDPOINT' => Command::set_url_scheme( "https://s3-{$this->hostname}/{$this->bucket_name}/" ),
				'S3_UPLOADS_BUCKET' => "{$this->bucket_name}",
				'S3_UPLOADS_BUCKET_URL' => Command::set_url_scheme( "https://s3-{$this->hostname}" ),
				'S3_UPLOADS_KEY' => 'admin',
				'S3_UPLOADS_SECRET' => 'password',
				'S3_UPLOADS_REGION' => 'us-east-1',
				'S3_CONSOLE_URL' => Command::set_url_scheme( "https://s3-console-{$this->hostname}" ),
				'TACHYON_URL' => Command::set_url_scheme( "{$this->url}tachyon" ),
				'PHP_SENDMAIL_PATH' => '/bin/false',
				'ALTIS_ANALYTICS_PINPOINT_ENDPOINT' => Command::set_url_scheme( "https://pinpoint-{$this->hostname}" ),
				'ALTIS_ANALYTICS_COGNITO_ENDPOINT' => Command::set_url_scheme( "https://cognito-{$this->hostname}" ),
				// Enables XDebug for all processes and allows setting remote_host externally for Linux support.
				'XDEBUG_CONFIG' => sprintf(
					'client_host=%s',
					Command::is_linux() && ! Command::is_wsl() ? '172.17.0.1' : 'host.docker.internal'
				),
				'PHP_IDE_CONFIG' => "serverName={$this->hostname}",
				'XDEBUG_SESSION' => $this->hostname,
				// Set XDebug mode, fall back to "off" to avoid any performance hits.
				'XDEBUG_MODE' => $this->args['xdebug'] ?? 'off',
			],
		];

		// Forward CI env var - set by Travis, Circle CI, GH Actions and more...
		if ( getenv( 'CI' ) ) {
			$services['environment']['CI'] = getenv( 'CI' );
		}

		return $services;
	}

	/**
	 * Get the PHP container service.
	 *
	 * @return array
	 */
	protected function get_service_php() : array {
		return [
			'php' => array_merge(
				[
					'container_name' => "{$this->project_name}-php",
				],
				$this->get_php_reusable()
			),
		];
	}

	/**
	 * Get the NodeJS container service.
	 *
	 * @return array
	 */
	protected function get_service_nodejs() : array {
		$config = $this->get_config();

		// Read package.json from nodejs.path to get the Node.js version to use.
		$package_json = json_decode( file_get_contents( "{$config['nodejs']['path']}/package.json" ), true );
		$version = $package_json['engines']['node'] ?? '20';

		return [
			'nodejs' => [
				'image' => "node:{$version}-bookworm-slim",
				'container_name' => "{$this->project_name}-nodejs",
				'ports' => [
					'3000',
				],
				'volumes' => [
					"../{$config['nodejs']['path']}/:/usr/src/app",
				],
				'working_dir' => '/usr/src/app',
				'command' => 'sh -c "npm install && npm run dev"',
				'networks' => [
					'proxy',
					'default',
				],
				'labels' => [
					'traefik.frontend.priority=1',
					'traefik.port=3000',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=HostRegexp:nodejs-{$this->hostname}",
					"traefik.domain=nodejs-{$this->hostname}",
				],
				'environment' => [
					'ALTIS_ENVIRONMENT_NAME' => $this->project_name,
					'ALTIS_ENVIRONMENT_TYPE' => 'local',
				],
			],
		];
	}

	/**
	 * Webgrind service container for viewing Xdebug profiles.
	 *
	 * @return array
	 */
	protected function get_service_webgrind() : array {
		return [
			'webgrind' => [
				'container_name' => "{$this->project_name}-webgrind",
				'image' => 'wodby/webgrind:1.9',
				'networks' => [
					'proxy',
					'default',
				],
				'depends_on' => [
					'php',
				],
				'ports' => [
					'8080',
				],
				'volumes' => [
					'tmp:/tmp',
				],
				'labels' => [
					'traefik.port=8080',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=Host:{$this->hostname};PathPrefix:/webgrind;PathPrefixStrip:/webgrind",
				],
				'environment' => [
					'WEBGRIND_DEFAULT_TIMEZONE' => 'UTC',
				],
			],
		];
	}

	/**
	 * Get the Cavalcade service.
	 *
	 * @return array
	 */
	protected function get_service_cavalcade() : array {
		return [
			'cavalcade' => array_merge(
				[
					'container_name' => "{$this->project_name}-cavalcade",
					'entrypoint' => [
						'/usr/local/bin/cavalcade',
					],
					'user' => 'nobody:nobody',
					'restart' => 'unless-stopped',
				],
				$this->get_php_reusable()
			),
		];
	}

	/**
	 * Get the nginx service.
	 *
	 * @return array
	 */
	protected function get_service_nginx() : array {
		$config = $this->get_config();
		$domains = $config['domains'] ?? [];
		$domains = $domains ? ',' . implode( ',', $domains ) : '';

		return [
			'nginx' => [
				'image' => 'humanmade/altis-local-server-nginx:3.6.0',
				'container_name' => "{$this->project_name}-nginx",
				'networks' => [
					'proxy',
					'default',
				],
				'depends_on' => [
					'php',
				],
				'volumes' => [
					$this->get_app_volume(),
					'socket:/var/run/php-fpm',
				],
				'ports' => [
					'8080',
				],
				'labels' => [
					'traefik.frontend.priority=1',
					'traefik.port=8080',
					'traefik.protocol=https',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=HostRegexp:{$this->hostname},{subdomain:[A-Za-z0-9.-]+}.{$this->hostname}{$domains}",
					"traefik.domain={$this->hostname},*.{$this->hostname}{$domains}",
				],
				'environment' => [
					// Gzip compression now defaults to off to support Brotli compression via CloudFront.
					'GZIP_STATUS' => 'on',
					// Increase read response timeout when debugging.
					'READ_TIMEOUT' => ( $this->args['xdebug'] ?? 'off' ) !== 'off' ? '9000s' : '60s',
					// Disables rate limiting.
					'PHP_PUBLIC_POOL_ENABLE_RATE_LIMIT' => 'false',
				],
			],
		];
	}

	/**
	 * Get the Redis service.
	 *
	 * @return array
	 */
	protected function get_service_redis() : array {
		return [
			'redis' => [
				'image' => 'redis:7.0-alpine',
				'container_name' => "{$this->project_name}-redis",
				'ports' => [
					'6379',
				],
			],
		];
	}

	/**
	 * Get the DB service.
	 *
	 * @return array
	 */
	protected function get_service_db() : array {
		$version_map = [
			'8.0' => 'mysql:8.0',
		];

		$versions = array_keys( $version_map );
		$version = (string) $this->get_config()['mysql'];

		if ( ! in_array( $version, $versions, true ) ) {
			echo sprintf(
				"The configured MySQL version \"%s\" is not supported.\nTry one of the following:\n  - %s\n",
				// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
				$version,
				// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
				implode( "\n  - ", $versions )
			);
			exit( 1 );
		}

		$image = $version_map[ $version ];

		return [
			'db' => [
				'image' => $image,
				// Suppress mysql_native_password deprecation warning
				// Only affects in-place upgrades from MySQL 5.7 to 8.0.
				'command' => $version === '8.0' ? '--log-error-suppression-list=MY-013360' : '',
				'container_name' => "{$this->project_name}-db",
				'volumes' => [
					'db-data:/var/lib/mysql',
				],
				'ports' => [
					'3306',
				],
				'environment' => [
					'MYSQL_ROOT_PASSWORD' => 'wordpress',
					'MYSQL_DATABASE' => 'wordpress',
					'MYSQL_USER' => 'wordpress',
					'MYSQL_PASSWORD' => 'wordpress',
				],
				'healthcheck' => [
					'test' => [
						'CMD',
						'mysqladmin',
						'ping',
						'-h',
						'localhost',
						'-u',
						'wordpress',
						'-pwordpress',
					],
					'timeout' => '5s',
					'interval' => '5s',
					'retries' => 10,
				],
			],
		];
	}

	/**
	 * Get the S3 service.
	 *
	 * @return array
	 */
	protected function get_service_s3() : array {
		return [
			's3' => [
				'image' => 'minio/minio:RELEASE.2021-09-18T18-09-59Z',
				'container_name' => "{$this->project_name}-s3",
				'volumes' => [
					's3:/data:rw',
				],
				'ports' => [
					'9000',
					'9001',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'environment' => [
					'MINIO_DOMAIN' => "s3.localhost,{$this->hostname},s3-{$this->hostname},s3,localhost",
					'MINIO_REGION_NAME' => 'us-east-1',
					'MINIO_ROOT_USER' => 'admin',
					'MINIO_ROOT_PASSWORD' => 'password',
				],
				'command' => 'server /data --console-address ":9001"',
				'healthcheck' => [
					'test' => [
						'CMD',
						'curl',
						'-f',
						'http://localhost:9000/minio/health/live',
					],
					'interval' => '5s',
					'timeout' => '5s',
					'retries' => 3,
				],
				'labels' => [
					'traefik.docker.network=proxy',
					'traefik.api.port=9000',
					'traefik.api.protocol=http',
					"traefik.api.frontend.rule=HostRegexp:s3-{$this->hostname}",
					'traefik.console.port=9001',
					'traefik.console.protocol=http',
					"traefik.console.frontend.rule=HostRegexp:s3-console-{$this->hostname}",
					'traefik.client.port=9000',
					'traefik.client.protocol=http',
					'traefik.client.frontend.passHostHeader=false',
					"traefik.client.frontend.rule=HostRegexp:{$this->hostname},{subdomain:[A-Za-z0-9.-]+}.{$this->hostname},s3-{$this->hostname},localhost,s3-{$this->project_name}.localhost;PathPrefix:/uploads;AddPrefix:/{$this->bucket_name}",
					"traefik.domain=s3-{$this->hostname},s3-console-{$this->hostname}",
				],
			],
			's3-create-user' => [
				'image' => 'minio/mc:RELEASE.2021-09-02T09-21-27Z',
				'depends_on' => [
					's3' => [
						'condition' => 'service_healthy',
					],
				],
				'links' => [
					's3',
				],
				'environment' => [
					'MC_HOST_local' => 'http://admin:password@s3:9000',
				],
				'entrypoint' => "/bin/sh -c \"mc admin user add local newuser newpassword && mc admin policy set local readwrite user=newuser\"",
			],
			's3-create-bucket' => [
				'image' => 'minio/mc:RELEASE.2021-09-02T09-21-27Z',
				'depends_on' => [
					's3' => [
						'condition' => 'service_healthy',
					],
				],
				'links' => [
					's3',
				],
				'environment' => [
					'MC_HOST_local' => 'http://admin:password@s3:9000',
				],
				'entrypoint' => "/bin/sh -c \"mc mb -p local/{$this->bucket_name} && mc policy set public local/{$this->bucket_name}\"",
			],
			's3-sync-to-host' => [
				'image' => 'minio/mc:RELEASE.2021-09-02T09-21-27Z',
				'container_name' => "{$this->project_name}-s3-sync",
				'restart' => 'unless-stopped',
				'depends_on' => [
					's3-create-bucket' => [
						'condition' => 'service_completed_successfully',
					],
				],
				'environment' => [
					'MC_HOST_local' => 'http://admin:password@s3:9000',
				],
				'volumes' => [
					"{$this->root_dir}/content/uploads:/content/uploads:delegated",
				],
				'links' => [
					's3',
				],
				'entrypoint' => "/bin/sh -c \"mc mirror --watch --overwrite -a local/{$this->bucket_name} /content\"",
			],
		];
	}

	/**
	 * Get the Tachyon service.
	 *
	 * @return array
	 */
	protected function get_service_tachyon() : array {
		return [
			'tachyon' => [
				'image' => 'humanmade/tachyon:v3.0.7',
				'container_name' => "{$this->project_name}-tachyon",
				'ports' => [
					'8080',
				],
				'networks' => [
					'proxy',
				],
				'labels' => [
					'traefik.port=8080',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=HostRegexp:{$this->hostname},{subdomain:[A-Za-z0-9.-]+}.{$this->hostname};PathPrefix:/tachyon;ReplacePathRegex:^/tachyon/(.*) /uploads/$$1",
				],
				'environment' => [
					'AWS_REGION' => 'us-east-1',
					'S3_REGION' => 'us-east-1',
					'AWS_S3_BUCKET' => "{$this->bucket_name}",
					'S3_BUCKET' => "{$this->bucket_name}",
					'AWS_S3_ENDPOINT' => Command::set_url_scheme( "http://s3-{$this->hostname}/" ),
					'S3_ENDPOINT' => Command::set_url_scheme( "http://s3-{$this->hostname}/" ),
					'AWS_S3_CLIENT_ARGS' => 's3BucketEndpoint=true',
					'NODE_TLS_REJECT_UNAUTHORIZED' => 0,
					'AWS_ACCESS_KEY_ID' => 'newuser',
					'AWS_SECRET_ACCESS_KEY' => 'newpassword',
				],
				'external_links' => [
					"proxy:s3-{$this->hostname}",
				],
			],
		];
	}

	/**
	 * Get the Mailhog service.
	 *
	 * @return array
	 */
	protected function get_service_mailhog() : array {
		return [
			'mailhog' => [
				'image' => 'cd2team/mailhog:latest',
				'container_name' => "{$this->project_name}-mailhog",
				'ports' => [
					'8025',
					'1025',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'labels' => [
					'traefik.port=8025',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=Host:{$this->hostname};PathPrefix:/mailhog",
				],
				'environment' => [
					'MH_UI_WEB_PATH' => 'mailhog',
				],
			],
		];
	}

	/**
	 * Get the Analytics services.
	 *
	 * @return array
	 */
	protected function get_service_analytics() : array {
		return [
			'cognito' => [
				'container_name' => "{$this->project_name}-cognito",
				'ports' => [
					'3000',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'restart' => 'unless-stopped',
				'image' => 'humanmade/local-cognito:1.1.0',
				'labels' => [
					'traefik.port=3000',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=Host:cognito-{$this->hostname}",
					"traefik.domain=cognito-{$this->hostname}",
				],
			],
			'pinpoint' => [
				'container_name' => "{$this->project_name}-pinpoint",
				'ports' => [
					'3000',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'restart' => 'unless-stopped',
				'image' => 'humanmade/local-pinpoint:1.3.0',
				'labels' => [
					'traefik.port=3000',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=Host:pinpoint-{$this->hostname}",
					"traefik.domain=pinpoint-{$this->hostname}",
				],
				'environment' => [
					'INDEX_ROTATION' => 'OneDay',
				],
			],
		];
	}

	/**
	 * Get the XRay service.
	 *
	 * @return array
	 */
	protected function get_service_xray() : array {
		return [
			'xray' => [
				'image' => 'amazon/aws-xray-daemon:3.3.3',
				'container_name' => "{$this->project_name}-xray",
				'ports' => [
					'2000',
				],
				'environment' => [
					'AWS_ACCESS_KEY_ID' => 'YOUR_KEY_HERE',
					'AWS_SECRET_ACCESS_KEY' => 'YOUR_SECRET_HERE',
					'AWS_REGION' => 'us-east-1',
				],
			],
		];
	}

	/**
	 * Get the full docker compose configuration.
	 *
	 * @return array
	 */
	public function get_array() : array {
		$services = array_merge(
			$this->get_service_db(),
			$this->get_service_redis(),
			$this->get_service_php(),
			$this->get_service_nginx()
		);

		if ( $this->get_config()['xray'] ) {
			$services = array_merge( $services, $this->get_service_xray() );
		}

		if ( $this->get_config()['cavalcade'] ) {
			$services = array_merge( $services, $this->get_service_cavalcade() );
		}

		$services = array_merge(
			$services,
			$this->get_service_mailhog()
		);

		if ( $this->get_config()['s3'] ) {
			$services = array_merge( $services, $this->get_service_s3() );
		}

		if ( $this->get_config()['s3'] && $this->get_config()['tachyon'] ) {
			$services = array_merge( $services, $this->get_service_tachyon() );
		}

		if ( $this->get_config()['analytics'] && $this->get_config()['elasticsearch'] ) {
			$services = array_merge( $services, $this->get_service_analytics() );
		}

		if ( strpos( $this->args['xdebug'] ?? false, 'profile' ) !== false ) {
			$services = array_merge( $services, $this->get_service_webgrind() );
		}

		if ( $this->get_config()['nodejs'] ) {
			$services = array_merge( $services, $this->get_service_nodejs() );
		}

		// Default compose configuration.
		$config = [
			// 'version' => '2.5',
			'services' => $services,
			'networks' => [
				'default' => null,
				'proxy' => [
					'name' => 'proxy',
					'external' => true,
				],
			],
			'volumes' => [
				'db-data' => null,
				'tmp' => null,
				's3' => null,
				'socket' => null,
			],
		];

		// Mount tmp volume locally if requested.
		if ( $this->args['tmp'] ?? false ) {
			@mkdir( "{$this->root_dir}/.tmp" );
			$config['volumes']['tmp'] = [
				'driver' => 'local',
				'driver_opts' => [
					'type' => 'none',
					'device' => "{$this->root_dir}/.tmp",
					'o' => 'bind',
				],
			];
		}

		// Handle mutagen volume according to args.
		if ( ! empty( $this->args['mutagen'] ) && $this->args['mutagen'] === 'on' ) {
			$config['volumes']['app'] = null;
			$config['x-mutagen'] = [
				'sync' => [
					'app' => [
						'alpha' => $this->root_dir,
						'beta' => 'volume://app',
						'configurationBeta' => [
							'permissions' => [
								'defaultOwner' => 'id:82',
								'defaultGroup' => 'id:82',
								'defaultFileMode' => '0664',
								'defaultDirectoryMode' => '0775',
							],
						],
						'mode' => 'two-way-resolved',
					],
				],
			];
			// Add ignored paths.
			if ( ! empty( $this->get_config()['ignore-paths'] ) ) {
				$config['x-mutagen']['sync']['app']['ignore'] = [
					'paths' => array_values( (array) $this->get_config()['ignore-paths'] ),
				];
			}
		}

		// Initialize plugins and run them.
		if ( ! empty( $this->extra ) ) {
			foreach ( $this->extra as $package_spec ) {
				/**
				 * Create the extension handler.
				 *
				 * @var Compose_Extension $handler Extension interface.
				 */
				$handler = new $package_spec['compose-extension']();
				$handler->set_config( $this, $this->args );
				$config = $handler->filter_compose( $config );
			}
		}

		return $config;
	}

	/**
	 * Get Yaml output for config.
	 *
	 * @return string
	 */
	public function get_yaml() : string {
		return Yaml::dump( $this->get_array(), 10, 2 );
	}

	/**
	 * Get the Local Server config from composer.json.
	 *
	 * @return array
	 */
	protected function get_config() : array {
		// Set the root directory required by Altis\get_config() if not available.
		if ( ! defined( 'Altis\\ROOT_DIR' ) ) {
			define( 'Altis\\ROOT_DIR', $this->root_dir );
		}

		$modules = Altis\get_config()['modules'] ?? [];

		$analytics_enabled = $modules['analytics']['enabled'] ?? false;
		$search_enabled = $modules['search']['enabled'] ?? true;

		$defaults = [
			's3' => $modules['cloud']['s3-uploads'] ?? true,
			'tachyon' => $modules['media']['tachyon'] ?? true,
			'analytics' => $analytics_enabled,
			'cavalcade' => $modules['cloud']['cavalcade'] ?? true,
			'kibana' => ( $analytics_enabled || $search_enabled ),
			'afterburner' => false,
			'xray' => $modules['cloud']['xray'] ?? true,
			'ignore-paths' => [],
			'php' => '8.2',
			'mysql' => '8.0',
			'nodejs' => $modules['nodejs'] ?? false,
		];

		return array_merge( $defaults, $modules['local-server'] ?? [] );
	}

	/**
	 * Get the main application volume adjusted for sharing config options.
	 *
	 * @return string
	 */
	protected function get_app_volume() : string {
		if ( ! empty( $this->args['mutagen'] ) && $this->args['mutagen'] === 'on' ) {
			return 'app:/usr/src/app:delegated';
		}
		return "{$this->root_dir}:/usr/src/app:delegated";
	}
}
