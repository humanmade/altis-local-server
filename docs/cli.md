# Running CLI Commands

You can run any command on the web server container using the `composer server exec` command. You need to prepend the command with the options delimiter `--`.

For example to run a composer installed binary like `phpcs` run:

```sh
composer server exec -- vendor/bin/phpcs
```

Or to show all environment variables:

```sh
composer server exec -- printenv
```

## WP CLI

Local Server provides special support for [WP CLI](https://wp-cli.org/) commands via the `composer server cli --` command. Prepend all your commends with `composer server cli --` and drop the proceeding `wp`. For example, to list all posts:

```sh
composer server cli -- post list
```

To install a new language file and activate it:

```sh
composer server cli -- language core install fr_FR
```

CLI commands via Local Server also support piping for more complex shell commands.

### Importing a database backup

To import a database backup with local server, you will need to have a database backup file in a location that is accessible from the project root.

```sh
composer server cli -- db import database.sql
```

**Note:** For privacy reasons, any database backups that are version controlled should be scrubbed as to be free from any personal identifying information and care should be taken that database backup files committed to the repository are free from personal data.
