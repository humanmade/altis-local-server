---
order: 100
---
# Extra Containers

Local Server provides a series of Docker containers for the base Altis behaviour. Modules and other packages may add additional
containers (typically called "sidecar" containers) or alter the container behaviour through the extension system.


## How Local Server works

Local Server internally uses Docker Compose to create and manage containers for the Altis environment. When a user runs the
`composer server start` command, Local Server dynamically provisions a `docker-compose.yml` file based on the user's preferences.
(This file is only regenerated when starting Local Server to avoid conflicts or surprising behaviour for users.)

Other commands such as `composer server logs` are passthrough-style commands, which primarily wrap `docker-compose` commands, and
which work with Docker Compose server names.

Domain routing is internally handled using [Traefik Proxy](https://doc.traefik.io/traefik/) and each service can register
user-accessible routes using the [label configuration system](https://doc.traefik.io/traefik/providers/docker/).

Extensions register a class which has the ability to filter the `docker-compose.yml` data before it is written out to a file,
allowing the extensions to register additional services and containers, or modify the configuration in other ways.


## Writing an extension

Any Composer package (including the root package, i.e. the project) can specify an extension class, which is dynamically loaded by
Local Server and which receives the `docker-compose.yml` data to filter.

Packages should implement the [`Altis\Local_Server\Composer\Compose_Extension`
interface](https://github.com/humanmade/altis-local-server/blob/master/inc/composer/class-compose-extension.php) with the relevant
methods.

**Note:** This class will be loaded in the Composer context, not in WordPress, and neither hooks nor the full codebase will be
loaded. Notably, functions like `Altis\get_config()` will not return default values as a result.

For example, to add a sidecar service called `foo`:

```php
namespace MyModule;

use Altis\Local_Server\Composer\{Compose_Extension, Docker_Compose_Generator};

class Local_Server_Extension implements Compose_Extension {
	protected Docker_Compose_Generator $generator;

	public function set_config( Docker_Compose_Generator $generator, array $args ) : void {
		$this->generator = $generator;
	}

	public function filter_compose( array $config ) : array {
		$config['services']['foo'] = [
			'container_name' => "{$this->generator->project_name}-foo",
			'image' => 'hello-world',
		];

		return $config;
	}
```

This class should then be specified in the package's `composer.json` as `extra.altis.local-server.compose-extension`:

```json
{
	"extra": {
		"altis": {
			"local-server": {
				"compose-extension": "Altis\\Enhanced_Search\\Local_Server_Extension"
			}
		}
	}
}
```
