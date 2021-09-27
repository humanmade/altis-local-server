# Local Server

![](./assets/banner-local-server.png)

**Note:** Local Server is a Docker-based environment. If you experience any issues running it consult the [Local Chassis](docs://local-chassis) documentation for an alternative local environment.

The Local Server module providers a local development environment for Altis projects. It is built on a containerized architecture using Docker images and Docker Compose to provide drop-in replacements for most components of the Cloud infrastructure.

## Installing

**Windows Users:** Consult the [Windows setup documentation](./windows.md) to set up a WSL environment before using Local Server.

Local Server uses Docker for containerization, therefore you must install the Docker runtime on your computer as a prerequisite. Download and install Docker for your OS at [https://www.docker.com/get-started](https://www.docker.com/get-started).

Once Docker is installed and running, you are ready to start the Local Server. Local Server uses the command line via the `composer` command.

Navigate your shell to your project's directory. You should already have installed Altis by running `composer install` or `composer create-project` but if not, do so now. See [Creating A New Altis Project](https://www.altis-dxp.com/resources/docs/getting-started/#creating-a-new-altis-project).

### Experimental Features

You may find that file sharing performance or server response times are slower than you would like on Windows or MacOS. Local Server provides an experimental integration with [Mutagen](https://mutagen.io/) to resolve this.

See the [Mutagen set up guide for detailed instructions on how to install and run it](./mutagen-file-sharing.md).

## Starting the Local Server

To start the Local Server, run `composer server`. The first time you run this it will download all the necessary Docker images.

Once the initial download and install has completed, you should see the output:

```sh
Installed database.
WP Username:	admin
WP Password:	password
Startup completed.
To access your site visit: https://my-site.altis.dev/
```

Visiting your site's URL should now work. Visit `/wp-admin/` and login with the username `admin` and password `password` to get started!

> [If the server does not start for any reason take a look at the troubleshooting guide](./troubleshooting.md)

The subdomain used for the project can be configured via the `modules.local-server.name` setting:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"local-server": {
					"name": "my-project"
				}
			}
		}
	}
}
```

## Available Commands

* `composer server start [--xdebug=<mode>] [--mutagen]` - Starts the containers.
  * `--xdebug=<mode>` will enable Xdebug. The `mode` is optional and defaults to `debug`. Available values are `off`, `develop`, `debug`, `profile`, `coverage`, `gcstats` and `trace`.
  * `--mutagen` will enable Mutagen for container file sharing.
* `composer server stop [<service>]` - Stops the containers or specified service.
* `composer server restart [<service>]` - Restart a given container, or all containers if none is provided. Available values are `nginx`, `php`, `db`, `redis`, `cavalcade`, `tachyon`, `s3` and `elasticsearch`.
* `composer server destroy` - Stops and destroys all containers.
* `composer server status` - Displays the status of all containers.
* `composer server logs <service>` - Tail the logs from a given service, defaults to `php`, available options are `nginx`, `php`, `db`, `redis`, `cavalcade`, `tachyon`, `s3` and `elasticsearch`.
* `composer server shell` - Logs in to the PHP container.
* `composer server cli -- <command>` - Runs a WP CLI command, you should omit the 'wp' for example `composer server cli -- info`
  * `composer server cli -- db import database.sql` - Imports a database file located in the project root.
* `composer server exec -- <command>` - Runs any command on the PHP container.
* `composer server db` - Logs into MySQL on the DB container.
  * `composer server db info` - Print MySQL connection details.
  * `composer server db sequel` - Opens a connection to the database in [Sequel Pro](https://sequelpro.com).
* `composer server import-uploads` - Syncs files from `content/uploads` to the s3 container.
