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
			'8.4' => 'humanmade/altis-local-server-php:8.4.4',
			'8.3' => 'humanmade/altis-local-server-php:8.3.19',
			'8.2' => 'humanmade/altis-local-server-php:8.2.33',
			'8.1' => 'humanmade/altis-local-server-php:6.0.27',
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
			$afterburner_versions = [ '0.5', '1.0' ];
			$afterburner_version = $this->get_config()['afterburner'] === true ? '0.5' : number_format( (float) $this->get_config()['afterburner'], 1 );

			if ( ! in_array( $afterburner_version, $afterburner_versions, true ) ) {
				echo sprintf(
					"The configured Afterburner version \"%s\" is not supported.\nTry one of the following:\n  - %s\n",
					// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
					$afterburner_version,
					// phpcs:ignore HM.Security.EscapeOutput.OutputNotEscaped
					implode( "\n  - ", $afterburner_versions )
				);
				exit( 1 );
			}

			$volumes[] = "{$this->config_dir}/afterburner-{$afterburner_version}.ini:/usr/local/etc/php/conf.d/afterburner.ini";
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
				'S3_UPLOADS_ENDPOINT' => Command::set_url_scheme( "https://s3-{$this->hostname}" ),
				'S3_UPLOADS_BUCKET' => "{$this->bucket_name}",
				'S3_UPLOADS_BUCKET_URL' => Command::set_url_scheme( "https://s3-{$this->hostname}" ),
				'S3_UPLOADS_KEY' => 'admin',
				'S3_UPLOADS_SECRET' => 'password',
				'S3_UPLOADS_REGION' => 'us-east-1',
				'TACHYON_URL' => Command::set_url_scheme( "{$this->url}tachyon" ),
				'PHP_SENDMAIL_PATH' => '/bin/false',
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
	 * Default memory limit for all services that don't specify one.
	 *
	 * @param array $args The services array to apply defaults to.
	 *
	 * @return array The modified array.
	 */
	protected function apply_service_defaults( array $args ) : array {
		$mem_limit = getenv( 'LS_MEM_LIMIT' ) ?: '1g';
		foreach ( $args as $service => $service_args ) {
			if ( isset( $service_args['mem_limit'] ) ) {
				continue;
			}
			$args[ $service ]['mem_limit'] = $mem_limit;
		}

		return $args;
	}

	/**
	 * Get the PHP container service.
	 *
	 * @return array
	 */
	protected function get_service_php() : array {
		return $this->apply_service_defaults( [
			'php' => array_merge(
				[
					'container_name' => "{$this->project_name}-php",
				],
				$this->get_php_reusable()
			),
		] );
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

		return $this->apply_service_defaults( [
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
					'traefik.enable=true',
					'traefik.docker.network=proxy',
					"traefik.http.routers.{$this->project_name}-nodejs.rule=HostRegexp(`nodejs-{$this->hostname}`)",
					"traefik.http.routers.{$this->project_name}-nodejs.priority=1",
					"traefik.http.routers.{$this->project_name}-nodejs.entrypoints=web,websecure",
					"traefik.http.routers.{$this->project_name}-nodejs.service={$this->project_name}-nodejs",
					"traefik.http.services.{$this->project_name}-nodejs.loadbalancer.server.port=3000",
				],
				'environment' => [
					'ALTIS_ENVIRONMENT_NAME' => $this->project_name,
					'ALTIS_ENVIRONMENT_TYPE' => 'local',
				],
			],
		] );
	}

	/**
	 * Webgrind service container for viewing Xdebug profiles.
	 *
	 * @return array
	 */
	protected function get_service_webgrind() : array {
		return $this->apply_service_defaults( [
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
					'traefik.enable=true',
					'traefik.docker.network=proxy',
					"traefik.http.routers.{$this->project_name}-webgrind.rule=Host(`{$this->hostname}`) && PathPrefix(`/webgrind`)",
					"traefik.http.routers.{$this->project_name}-webgrind.entrypoints=web,websecure",
					"traefik.http.routers.{$this->project_name}-webgrind.service={$this->project_name}-webgrind",
					"traefik.http.routers.{$this->project_name}-webgrind.middlewares={$this->project_name}-webgrind-stripprefix",
					"traefik.http.middlewares.{$this->project_name}-webgrind-stripprefix.stripprefix.prefixes=/webgrind",
					"traefik.http.services.{$this->project_name}-webgrind.loadbalancer.server.port=8080",
				],
				'environment' => [
					'WEBGRIND_DEFAULT_TIMEZONE' => 'UTC',
				],
			],
		] );
	}

	/**
	 * Get the Cavalcade service.
	 *
	 * @return array
	 */
	protected function get_service_cavalcade() : array {
		return $this->apply_service_defaults( [
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
		] );
	}

	/**
	 * Get the nginx service.
	 *
	 * @return array
	 */
	protected function get_service_nginx() : array {
		$config = $this->get_config();
		$host_rule = $this->get_primary_host_rule( $config['domains'] ?? [] );

		return $this->apply_service_defaults( [
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
					'traefik.enable=true',
					'traefik.docker.network=proxy',
					"traefik.http.routers.{$this->project_name}-nginx.rule=" . $host_rule,
					"traefik.http.routers.{$this->project_name}-nginx.priority=1",
					"traefik.http.routers.{$this->project_name}-nginx.entrypoints=web,websecure",
					"traefik.http.routers.{$this->project_name}-nginx.tls=true",
					"traefik.http.routers.{$this->project_name}-nginx.service={$this->project_name}-nginx",
					"traefik.http.services.{$this->project_name}-nginx.loadbalancer.server.port=8080",
					"traefik.http.services.{$this->project_name}-nginx.loadbalancer.server.scheme=https",
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
		] );
	}

	/**
	 * Get the Redis service.
	 *
	 * @return array
	 */
	protected function get_service_redis() : array {
		return $this->apply_service_defaults( [
			'redis' => [
				'image' => 'redis:7.0-alpine',
				'container_name' => "{$this->project_name}-redis",
				'ports' => [
					'6379',
				],
			],
		] );
	}

	/**
	 * Get the DB service.
	 *
	 * @return array
	 */
	protected function get_service_db() : array {
		$version_map = [
			'8.0' => 'mysql:8.0.44',
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

		return $this->apply_service_defaults( [
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
		] );
	}

	/**
	 * Get the S3 service.
	 *
	 * @return array
	 */
	protected function get_service_s3() : array {
		return $this->apply_service_defaults( [
			's3' => [
				'image' => 'versity/versitygw:v1.1.0',
				'container_name' => "{$this->project_name}-s3",
				'volumes' => [
					's3-data:/data:rw',
				],
				'ports' => [
					'7070',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'environment' => [
					'ROOT_ACCESS_KEY' => 'admin',
					'ROOT_SECRET_KEY' => 'password',
					'AWS_REGION' => 'us-east-1',
				],
				'command' => [
					'posix',
					'/data',
					'--port',
					'7070',
				],
				'healthcheck' => [
					'test' => [
						'CMD-SHELL',
						'nc -z localhost 7070 || exit 1',
					],
					'interval' => '5s',
					'timeout' => '5s',
					'retries' => 3,
				],
				'labels' => [
					'traefik.enable=true',
					'traefik.docker.network=proxy',
					// S3 API router.
					"traefik.http.routers.{$this->project_name}-s3-api.rule=Host(`s3-{$this->hostname}`)",
					"traefik.http.routers.{$this->project_name}-s3-api.entrypoints=web,websecure",
					"traefik.http.routers.{$this->project_name}-s3-api.service={$this->project_name}-s3-api",
					"traefik.http.services.{$this->project_name}-s3-api.loadbalancer.server.port=7070",
					// S3 Client router (for uploads path).
					"traefik.http.routers.{$this->project_name}-s3-client.rule=" . $this->get_s3_client_host_rule() . ' && PathPrefix(`/uploads`)',
					"traefik.http.routers.{$this->project_name}-s3-client.entrypoints=web,websecure",
					"traefik.http.routers.{$this->project_name}-s3-client.service={$this->project_name}-s3-client",
					"traefik.http.routers.{$this->project_name}-s3-client.middlewares={$this->project_name}-s3-client-prefix,{$this->project_name}-s3-client-host",
					"traefik.http.middlewares.{$this->project_name}-s3-client-prefix.replacepathregex.regex=^/uploads/(.*)",
					"traefik.http.middlewares.{$this->project_name}-s3-client-prefix.replacepathregex.replacement=/{$this->bucket_name}/uploads/$$1",
					"traefik.http.middlewares.{$this->project_name}-s3-client-host.headers.customrequestheaders.Host=s3-{$this->hostname}",
					"traefik.http.services.{$this->project_name}-s3-client.loadbalancer.server.port=7070",
				],
			],
			's3-create-bucket' => [
				'image' => 'amazon/aws-cli:2.31.0',
				'depends_on' => [
					's3' => [
						'condition' => 'service_healthy',
					],
				],
				'links' => [
					's3',
				],
				'environment' => [
					'AWS_ACCESS_KEY_ID' => 'admin',
					'AWS_SECRET_ACCESS_KEY' => 'password',
					'AWS_DEFAULT_REGION' => 'us-east-1',
				],
				'entrypoint' => [
					'/bin/sh',
					'-c',
					sprintf(
						"aws s3api create-bucket --bucket %s --endpoint-url=http://s3:7070 || true; aws s3api put-bucket-policy --bucket %s --policy '{\"Version\":\"2012-10-17\",\"Statement\":[{\"Sid\":\"PublicReadUploads\",\"Effect\":\"Allow\",\"Principal\":\"*\",\"Action\":[\"s3:GetObject\"],\"Resource\":[\"arn:aws:s3:::%s/uploads/*\"]}]}' --endpoint-url=http://s3:7070 || true",
						$this->bucket_name,
						$this->bucket_name,
						$this->bucket_name
					),
				],
			],
			's3-sync-to-host' => [
				'image' => 'amazon/aws-cli:2.31.0',
				'container_name' => "{$this->project_name}-s3-sync",
				'restart' => 'unless-stopped',
				'depends_on' => [
					's3-create-bucket' => [
						'condition' => 'service_completed_successfully',
					],
				],
				'links' => [
					's3',
				],
				'environment' => [
					'AWS_ACCESS_KEY_ID' => 'admin',
					'AWS_SECRET_ACCESS_KEY' => 'password',
					'AWS_DEFAULT_REGION' => 'us-east-1',
				],
				'volumes' => [
					"{$this->root_dir}/content/uploads:/content/uploads:delegated",
				],
				'entrypoint' => [
					'/bin/sh',
					'-c',
					sprintf(
						'while true; do aws s3 sync s3://%s/uploads /content/uploads --endpoint-url=http://s3:7070 --only-show-errors; sleep 10; done',
						$this->bucket_name
					),
				],
			],
		] );
	}

	/**
	 * Get the Tachyon service.
	 *
	 * @return array
	 */
	protected function get_service_tachyon() : array {
		$config = $this->get_config();
		$host_rule = $this->get_primary_host_rule( $config['domains'] ?? [] );

		return $this->apply_service_defaults( [
			'tachyon' => [
				'image' => 'humanmade/tachyon:v3.0.7',
				'container_name' => "{$this->project_name}-tachyon",
				'ports' => [
					'8080',
				],
				'networks' => [
					'proxy',
					'default',
				],
				'labels' => [
					'traefik.enable=true',
					'traefik.docker.network=proxy',
					"traefik.http.routers.{$this->project_name}-tachyon.rule=" . $host_rule . ' && PathPrefix(`/tachyon`)',
					"traefik.http.routers.{$this->project_name}-tachyon.entrypoints=web,websecure",
					"traefik.http.routers.{$this->project_name}-tachyon.service={$this->project_name}-tachyon",
					"traefik.http.routers.{$this->project_name}-tachyon.middlewares={$this->project_name}-tachyon-replacepath",
					"traefik.http.middlewares.{$this->project_name}-tachyon-replacepath.replacepathregex.regex=^/tachyon/(.*)",
					"traefik.http.middlewares.{$this->project_name}-tachyon-replacepath.replacepathregex.replacement=/uploads/$$1",
					"traefik.http.services.{$this->project_name}-tachyon.loadbalancer.server.port=8080",
				],
				'environment' => [
					'S3_REGION' => 'us-east-1',
					'S3_BUCKET' => "{$this->bucket_name}",
					// Use direct internal connection to S3 service for better performance
					// VersityGW is S3-compatible and supports path-style requests
					'S3_ENDPOINT' => Command::set_url_scheme( "https://s3-{$this->hostname}/" ),
					'S3_FORCE_PATH_STYLE' => 'true',
					'NODE_TLS_REJECT_UNAUTHORIZED' => 0,
					'AWS_ACCESS_KEY_ID' => 'admin',
					'AWS_SECRET_ACCESS_KEY' => 'password',
				],
				'links' => [
					's3',
					's3:s3.localhost',
				],
				'external_links' => [
					"proxy:s3-{$this->hostname}",
				],
			],
		] );
	}

	/**
	 * Get the Mailhog service.
	 *
	 * @return array
	 */
	protected function get_service_mailhog() : array {
		return $this->apply_service_defaults( [
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
					'traefik.enable=true',
					'traefik.docker.network=proxy',
					"traefik.http.routers.{$this->project_name}-mailhog.rule=Host(`{$this->hostname}`) && PathPrefix(`/mailhog`)",
					"traefik.http.routers.{$this->project_name}-mailhog.entrypoints=web,websecure",
					"traefik.http.routers.{$this->project_name}-mailhog.service={$this->project_name}-mailhog",
					"traefik.http.services.{$this->project_name}-mailhog.loadbalancer.server.port=8025",
				],
				'environment' => [
					'MH_UI_WEB_PATH' => 'mailhog',
				],
			],
		] );
	}

	/**
	 * Get the XRay service.
	 *
	 * @return array
	 */
	protected function get_service_xray() : array {
		return $this->apply_service_defaults( [
			'xray' => [
				'image' => 'amazon/aws-xray-daemon:3.3.14',
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
		] );
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

		if ( strpos( $this->args['xdebug'] ?? false, 'profile' ) !== false ) {
			$services = array_merge( $services, $this->get_service_webgrind() );
		}

		if ( $this->get_config()['nodejs'] ) {
			$services = array_merge( $services, $this->get_service_nodejs() );
		}

		// Default compose configuration.
		$config = [
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
				's3-data' => null,
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

		$search_enabled = $modules['search']['enabled'] ?? true;

		$defaults = [
			's3' => $modules['cloud']['s3-uploads'] ?? true,
			'tachyon' => $modules['media']['tachyon'] ?? true,
			'cavalcade' => $modules['cloud']['cavalcade'] ?? true,
			'kibana' => ( $search_enabled ),
			'afterburner' => false,
			'xray' => $modules['cloud']['xray'] ?? true,
			'ignore-paths' => [],
			'php' => '8.3',
			'mysql' => '8.0',
			'nodejs' => $modules['nodejs'] ?? false,
		];

		return array_merge( $defaults, $modules['local-server'] ?? [] );
	}

	/**
	 * Build the primary host rule used by routers.
	 *
	 * @param array $extra_domains Additional domains to include.
	 * @return string
	 */
	protected function get_primary_host_rule( array $extra_domains = [] ) : string {
		$host_rules = [
			"Host(`{$this->hostname}`)",
			"HostRegexp(`{subdomain:[A-Za-z0-9-]+}.{$this->hostname}`)",
		];

		$extra_domains = array_filter( array_map( 'trim', $extra_domains ) );
		foreach ( $extra_domains as $domain ) {
			$host_rules[] = "Host(`{$domain}`)";
			$host_rules[] = "HostRegexp(`{subdomain:[A-Za-z0-9-]+}.{$domain}`)";
		}

		return '(' . implode( ' || ', array_unique( $host_rules ) ) . ')';
	}

	/**
	 * Build the host rule for S3 client uploads routing.
	 *
	 * @return string
	 */
	protected function get_s3_client_host_rule() : string {
		$client_hosts = [
			"Host(`{$this->hostname}`)",
			"HostRegexp(`{subdomain:[A-Za-z0-9-]+}.{$this->hostname}`)",
			"Host(`s3-{$this->hostname}`)",
			'Host(`localhost`)',
			"Host(`s3-{$this->project_name}.localhost`)",
		];

		return '(' . implode( ' || ', array_unique( $client_hosts ) ) . ')';
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
