# Mutagen File Sharing

[Mutagen](https://mutagen.io/) is a powerful tool for optimising file system mounts among other things (not just for Docker!).

Local Server provides an _experimental_ integration with Mutagen to improve the performance of your development environment. This is unlikely to provide much benefit if you use Linux as your operating system.

## Installation

To get started you will need to [install the Mutagen Compose Beta](https://github.com/mutagen-io/mutagen-compose). The beta version has integrated `docker compose` orchestration support through the `mutagen-compose` command.

In order to work you must be using Docker Compose v2, it is recommended to update Docker Desktop or Docker Engine to the latest version to get this.

### MacOS

The easiest way to install Mutagen on Mac is using [HomeBrew](https://brew.sh/).

```sh
brew install mutagen-io/mutagen/mutagen-beta mutagen-io/mutagen/mutagen-compose-beta
```

For all available builds see the [releases page](https://github.com/mutagen-io/mutagen-compose/releases) for the latest release and open the assets section.

If using the download directly open the zipped file and copy the `mutagen-compose` file to your `/usr/local/bin` directory. The `mutagen-compose` command should now be available in your terminal.

### Other OSes

For all other available binaries including Windows see the [releases page](https://github.com/mutagen-io/mutagen-compose/releases) for the latest release and open the assets section.

For Windows once downloaded unzip file and move `mutagen-compose.exe` to somewhere referenced in your `$PATH` environment variable.

Although not recommended for Linux you may wish to use Mutagen with WSL, in which case you can use the appropriate Linux binary:

- 386 for 32-Bit systems
- AMD64 for 64-Bit systems
- ARM or ARM64 for ARM systems

## Activating Mutagen

Once installed you can set Mutagen up by running:

```
composer server start --mutagen
```

After installing Mutagen and running `composer server start --mutagen` for the first time, the Mutagen container will be built and Mutagen will perform an initial synchronization. This may take several minutes depending on the size of your project.

If you find that there are issues or problems with using Mutagen you can deactivate it by running the `start` command again without the `--mutagen` flag.

## Configuring Shared Files

Mutagen not only improves file read times but also allows you to optimise which files are shared between your host machine and the Docker containers.

If you have no need for huge `node_modules` directories for example to be shared with the containers you can specify it in an array in your Altis `composer.json` config like so:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"local-server": {
					"ignore-paths": [
						".git",
						"node_modules",
						".DS_Store"
					]
				}
			}
		}
	}
}
```

The same file patterns available in `.gitignore` files can be used, so `*`, `**` and `!` operators are all valid.
