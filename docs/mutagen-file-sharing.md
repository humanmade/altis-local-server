# Mutagen File Sharing

[Mutagen](https://mutagen.io/) is a powerful tool for optimising file system mounts among other things (not just for Docker!).

Local Server provides an experimental integration with Mutagen to improve the performance of your development environment. This is unlikely to provide much benefit if you use Linux as your operating system.

## Installation

To get started you will need to [install Mutagen Beta](https://mutagen.io/documentation/introduction/installation#development-channels). The beta version has integrated `docker compose` orchestration support through the `mutagen compose` command.

### MacOS

The easiest way to install Mutagen on Mac is using [HomeBrew](https://brew.sh/).

- With HomeBrew: `brew install mutagen-io/mutagen/mutagen-beta`
- [Mac - Intel build (v0.12.0-beta7)](https://github.com/mutagen-io/mutagen/releases/download/v0.12.0-beta7/mutagen_darwin_amd64_v0.12.0-beta7.tar.gz)
- [Mac - ARM Build (v0.12.0-beta7))](https://github.com/mutagen-io/mutagen/releases/download/v0.12.0-beta7/mutagen_darwin_arm64_v0.12.0-beta7.tar.gz)

If using the download directly open the zipped file and copy the `mutagen` file to your `/usr/local/bin` directory. The `mutagen` command should now be available in your terminal.

### Windows

- [32-bit build](https://github.com/mutagen-io/mutagen/releases/download/v0.12.0-beta7/mutagen_windows_386_v0.12.0-beta7.zip)
- [64-bit build](https://github.com/mutagen-io/mutagen/releases/download/v0.12.0-beta7/mutagen_windows_amd64_v0.12.0-beta7.zip)
- [ARM build](https://github.com/mutagen-io/mutagen/releases/download/v0.12.0-beta7/mutagen_windows_arm_v0.12.0-beta7.zip)

Once downloaded open the zip file and run `mutagen.exe` then follow the prompts on screen.

### Other Operating Systems

For all other operating systems check the [beta release assets list for the appropriate build](https://github.com/mutagen-io/mutagen/releases/tag/v0.12.0-beta7).

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
