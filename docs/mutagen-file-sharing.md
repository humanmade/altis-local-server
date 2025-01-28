# Mutagen File Sharing

[Mutagen](https://mutagen.io/) is a powerful tool for optimising file system mounts among other things (not just for Docker!).

Local Server provides an integration with Mutagen to improve the performance of your development environment. This is
unlikely to provide much benefit if you use Linux as your operating system.

## Installation

To get started you will need to [install the Mutagen Docker Desktop extension](https://hub.docker.com/u/mutagenio).

In order to work you must be using Docker Compose v2, it is recommended to update Docker Desktop or Docker Engine to the latest
version to get this.

## Activating Mutagen

Once installed you can set Mutagen up by running:

```shell
composer server start --mutagen
```

After installing Mutagen and running `composer server start --mutagen` for the first time, the Mutagen container will be built and
Mutagen will perform an initial synchronization. This may take several minutes depending on the size of your project.

If you find that there are issues or problems with using Mutagen you can deactivate it by running the `start` command again without
the `--mutagen` flag.

## Configuring Shared Files

Mutagen not only improves file read times but also allows you to optimise which files are shared between your host machine and the
Docker containers.

If you have no need for huge `node_modules` directories for example to be shared with the containers you can specify it in an array
in your Altis `composer.json` config like so:

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
