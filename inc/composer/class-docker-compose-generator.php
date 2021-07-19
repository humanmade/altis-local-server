<?php
/**
 * Local Server Docker Compose file generator.
 */

namespace Altis\Local_Server\Composer;

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
	protected $project_name;

	/**
	 * The Altis project root directory.
	 *
	 * @var string
	 */
	protected $root_dir;

	/**
	 * The docker-compose.yml directory.
	 *
	 * @var string
	 */
	protected $config_dir;

	/**
	 * The primary top level domain for the server.
	 *
	 * @var string
	 */
	protected $tld;

	/**
	 * The primary domain name for the project.
	 *
	 * @var string
	 */
	protected $hostname;

	/**
	 * An array of data passed to
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Create and configure the generator.
	 *
	 * @param string $project_name The docker compose project name.
	 * @param string $root_dir The project root directory.
	 * @param array $args An optional array of arguments to modify the behaviour of the generator.
	 */
	public function __construct( string $project_name, string $root_dir, array $args = [] ) {
		$this->project_name = $project_name;
		$this->root_dir = $root_dir;
		$this->config_dir = dirname( __DIR__, 2 ) . '/docker';
		$this->tld = 'altis.dev';
		$this->hostname = $this->project_name . '.' . $this->tld;
		$this->args = $args;
	}

	/**
	 * Get the PHP server configuration.
	 *
	 * @return array
	 */
	protected function get_php_reusable() : array {
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
			],
			'image' => 'humanmade/altis-local-server-php:4.0.0-dev',
			'links' => [
				'db:db-read-replica',
				's3:s3.localhost',
			],
			'external_links' => [
				"proxy:{$this->hostname}",
				"proxy:pinpoint-{$this->hostname}",
				"proxy:cognito-{$this->hostname}",
				"proxy:elasticsearch-{$this->hostname}",
				"proxy:s3-{$this->hostname}",
			],
			'volumes' => [
				$this->get_app_volume(),
				"{$this->config_dir}/php.ini:/usr/local/etc/php/conf.d/altis.ini",
				'socket:/var/run/php-fpm',
			],
			'networks' => [
				'proxy',
				'default',
			],
			'environment' => [
				'HOST_PATH' => $this->root_dir,
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
				'ELASTICSEARCH_HOST' => 'elasticsearch',
				'ELASTICSEARCH_PORT' => 9200,
				'AWS_XRAY_DAEMON_HOST' => 'xray',
				'S3_UPLOADS_ENDPOINT' => "https://{$this->tld}/",
				'S3_UPLOADS_BUCKET' => "s3-{$this->project_name}",
				'S3_UPLOADS_BUCKET_URL' => "https://s3-{$this->hostname}",
				'S3_UPLOADS_KEY' => 'admin',
				'S3_UPLOADS_SECRET' => 'password',
				'S3_UPLOADS_REGION' => 'us-east-1',
				'TACHYON_URL' => "https://{$this->hostname}/tachyon",
				'PHP_SENDMAIL_PATH' => '/usr/sbin/sendmail -t -i -S mailhog:1025',
				'ALTIS_ANALYTICS_PINPOINT_ENDPOINT' => "https://pinpoint-{$this->hostname}",
				'ALTIS_ANALYTICS_COGNITO_ENDPOINT' => "https://cognito-{$this->hostname}",
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

		if ( $this->get_config()['elasticsearch'] ) {
			$services['depends_on']['elasticsearch'] = [
				'condition' => 'service_healthy',
			];
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
			'php' => $this->get_php_reusable(),
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
		return [
			'nginx' => [
				'image' => 'humanmade/altis-local-server-nginx:3.3.0',
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
					"traefik.frontend.rule=HostRegexp:{$this->hostname},{subdomain:[a-z.-_]+}.{$this->hostname}",
				],
				'environment' => [
					// Gzip compression now defaults to off to support Brotli compression via CloudFront.
					'GZIP_STATUS' => 'on',
					// Increase read response timeout when debugging.
					'READ_TIMEOUT' => ( $this->args['xdebug'] ?? 'off' ) !== 'off' ? '9000s' : '60s',
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
				'image' => 'redis:3.2-alpine',
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
		return [
			'db' => [
				'image' => 'mysql:5.7',
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
	 * Get the Elasticsearch service.
	 *
	 * @return array
	 */
	protected function get_service_elasticsearch() : array {
		$mem_limit = getenv( 'ES_MEM_LIMIT' ) ?: '1g';
		return [
			'elasticsearch' => [
				'image' => 'humanmade/altis-local-server-elasticsearch:3.0.0',
				'ulimits' => [
					'memlock' => [
						'soft' => -1,
						'hard' => -1,
					],
				],
				'mem_limit' => $mem_limit,
				'volumes' => [
					'es-data:/usr/share/elasticsearch/data',
					"{$this->root_dir}/content/uploads/es-packages:/usr/share/elasticsearch/config/packages",
				],
				'ports' => [
					'9200',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'healthcheck' => [
					'test' => [
						'CMD-SHELL',
						'curl --silent --fail localhost:9200/_cluster/health || exit 1',
					],
					'interval' => '5s',
					'timeout' => '5s',
					'retries' => 25,
				],
				'labels' => [
					'traefik.port=9200',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=HostRegexp:elasticsearch-{$this->hostname}",
				],
				'environment' => [
					'http.max_content_length=10mb',
					// Force ES into single-node mode (otherwise defaults to zen discovery as
					// network.host is set in the default config).
					'discovery.type=single-node',
					// Reduce from default of 1GB of memory to 512MB.
					'ES_JAVA_OPTS=-Xms512m -Xmx512m',
				],
			],
		];
	}

	/**
	 * Get the Kibana service.
	 *
	 * @return array
	 */
	protected function get_service_kibana() : array {
		return [
			'kibana' => [
				'image' => 'blacktop/kibana:6.3',
				'networks' => [
					'proxy',
					'default',
				],
				'ports' => [
					'5601',
				],
				'labels' => [
					'traefik.port=5601',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=Host:{$this->hostname};PathPrefix:/kibana",
				],
				'depends_on' => [
					'elasticsearch' => [
						'condition' => 'service_healthy',
					],
				],
				'volumes' => [
					"{$this->config_dir}/kibana.yml:/usr/share/kibana/config/kibana.yml",
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
				'image' => 'minio/minio:RELEASE.2020-03-19T21-49-00Z',
				'volumes' => [
					's3:/data:rw',
				],
				'ports' => [
					'9000',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'environment' => [
					'MINIO_DOMAIN' => 's3.localhost,altis.dev,s3',
					'MINIO_REGION_NAME' => 'us-east-1',
					'MINIO_ACCESS_KEY' => 'admin',
					'MINIO_SECRET_KEY' => 'password',
				],
				'command' => 'server /data',
				'healthcheck' => [
					'test' => [
						'CMD',
						'curl',
						'-f',
						'http://localhost:9000/minio/health/live',
					],
					'interval' => '30s',
					'timeout' => '20s',
					'retries' => 3,
				],
				'labels' => [
					'traefik.port=9000',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=HostRegexp:s3-{$this->hostname}",
				],
			],
			's3-sync-to-host' => [
				'image' => 'minio/mc:RELEASE.2020-03-14T01-23-37Z',
				'restart' => 'unless-stopped',
				'depends_on' => [
					's3',
				],
				'volumes' => [
					"{$this->config_dir}/minio.json:/root/.mc/config.json",
					"{$this->root_dir}/content/uploads:/content/uploads:delegated",
				],
				'links' => [
					's3',
				],
				'entrypoint' => "/bin/sh -c \"mc mb -p local/s3-{$this->project_name} && mc policy set public local/s3-{$this->project_name} && mc mirror --watch --overwrite local/s3-{$this->project_name} /content\"",
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
				'image' => 'humanmade/tachyon:2.3.2',
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
					"traefik.frontend.rule=HostRegexp:{$this->hostname},{subdomain:[a-z.-_]+}.{$this->hostname};PathPrefix:/tachyon;ReplacePathRegex:^/tachyon/(.*) /uploads/$$1",
				],
				'environment' => [
					'AWS_REGION' => 'us-east-1',
					'AWS_S3_BUCKET' => "s3-{$this->project_name}",
					'AWS_S3_ENDPOINT' => "https://{$this->tld}/",
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
				'image' => 'mailhog/mailhog:latest',
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
				'ports' => [
					'3000',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'restart' => 'unless-stopped',
				'image' => 'humanmade/local-cognito:1.0.0',
				'labels' => [
					'traefik.port=3000',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=Host:cognito-{$this->hostname}",
				],
			],
			'pinpoint' => [
				'ports' => [
					'3000',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'restart' => 'unless-stopped',
				'image' => 'humanmade/local-pinpoint:1.2.2',
				'labels' => [
					'traefik.port=3000',
					'traefik.protocol=http',
					'traefik.docker.network=proxy',
					"traefik.frontend.rule=Host:pinpoint-{$this->hostname}",
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
				'image' => 'amazon/aws-xray-daemon:3.0.1',
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

		if ( $this->get_config()['elasticsearch'] ) {
			$services = array_merge( $services, $this->get_service_elasticsearch() );
		}

		$services = array_merge(
			$services,
			$this->get_service_s3(),
			$this->get_service_tachyon(),
			$this->get_service_mailhog()
		);

		if ( $this->get_config()['analytics'] && $this->get_config()['elasticsearch'] ) {
			$services = array_merge( $services, $this->get_service_analytics() );
		}

		if ( $this->get_config()['kibana'] && $this->get_config()['elasticsearch'] ) {
			$services = array_merge( $services, $this->get_service_kibana() );
		}

		// Default compose configuration.
		$config = [
			'version' => '2.3',
			'services' => $services,
			'networks' => [
				'default' => null,
				'proxy' => [
					'external' => [
						'name' => 'proxy',
					],
				],
			],
			'volumes' => [
				'db-data' => null,
				'es-data' => null,
				'tmp' => null,
				's3' => null,
				'socket' => null,
			],
		];

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
	 * Get a module config from composer.json.
	 *
	 * @param string $module The module to get the config for.
	 * @return array
	 */
	protected function get_config( $module = 'local-server' ) : array {
		// @codingStandardsIgnoreLine
		$json = file_get_contents( $this->root_dir . DIRECTORY_SEPARATOR . 'composer.json' );
		$composer_json = json_decode( $json, true );

		$config = ( $composer_json['extra']['altis']['modules'][ $module ] ?? [] );
		$defaults = [
			'analytics' => true,
			'cavalcade' => true,
			'elasticsearch' => true,
			'kibana' => true,
			'xray' => true,
			'ignore-paths' => [],
		];

		return array_merge( $defaults, $config );
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
