# Running WP CLI Commands

The Local Server allows you to run [WP CLI](https://wp-cli.org/) commands via the `composer local-server cli --` command. Prepend all your commends with `composer local-server cli --` and drop the proceeding `wp`. For example, to list all posts:

```sh
composer local-server cli -- post list
```

To install a a new language file and activate it:

```sh
composer local-server cli -- language core install fr_FR
```
