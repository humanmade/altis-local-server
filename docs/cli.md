# Running CLI Commands

You can run any command on the web server container using the `composer server exec` command. You need to prepend the command with
the options delimiter `--`.

For example to run a composer installed binary like `phpcs` run:

```sh
composer server exec -- vendor/bin/phpcs
```

Or to show all environment variables:

```sh
composer server exec -- printenv
```

## Shell sessions

You can also start a shell session on the web server container using the `composer server shell` command. This will start an
interactive shell session on the web server container.

You will normally be logged in as the `www-data` user, but you can run the shell as the root user by passing the `--root` option.

```sh
composer server shell --root
```

note: The `--root` option is only available when running the shell session on local server. Any changes you make to the 
container will not be carried over to the Altis environment.

```sh

## WP CLI

Local Server provides special support for [WP CLI](https://wp-cli.org/) commands via the `composer server cli --` command. Prepend
all your commends with `composer server cli --` and drop the proceeding `wp`. For example, to list all posts:

```sh
composer server cli -- post list
```

To install a new language file and activate it:

```sh
composer server cli -- language core install fr_FR
```

CLI commands via Local Server also support piping for more complex shell commands.

### Importing a database backup

To import a database backup with local server, you will need to have a database backup file in a location that is accessible from
the project root.

```sh
composer server cli -- db import database.sql
```

**Note:** For privacy reasons any database backups that are version controlled should have any personally identifiable information
removed and extra care should be taken to avoid committing database backup files containing personal data.

### Default Site URL

Altis supports specifying a default site URL that can be different from the root URL of the local environment. This is particularly useful in scenarios where the default site resides at a subpath rather than the root of the local URL. By configuring this option, users can ensure WP-CLI commands operate on the correct site by default.

You can define the default site URL in your project’s `composer.json` file under the `extra.altis.modules.local-server.default-site-url` property. Here’s an example:


```json
{
    "extra": {
        "altis": {
            "modules": {
                "local-server": {
                    "default-site-url": "my-site.altis.dev/en/"
                }
            }
        }
    }
}
```

Note: By setting `default-site-url`, the default site URL will only be overridden for WP-CLI commands. It does not affect other components or tools in your environment.
