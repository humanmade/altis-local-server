# Database

## Available Versions

Altis supports MySQL version `8.0`.

## Interacting with the Database

You can log into MySQL and run any query in the default database (`wordpress`) by using:

```sh
composer server db
```

Use `composer server db info` to retrieve MySQL info and connection details:

```sh
Root password:  wordpress

Database:       wordpress
User:           wordpress
Password:       wordpress

Host:           0.0.0.0
Port:           32809

Version:        8.0
MySQL link:     mysql://wordpress:wordpress@0.0.0.0:32809/wordpress
```

Use `composer server db sequel` to open the database in Sequel Ace. This command can only be run under MacOS and
requires [Sequel Ace](https://sequel-ace.com//) to be installed on your computer.

Use `composer server db tableplus` to open the database in TablePlus. This command requires [TablePlus](https://tableplus.com/) to
be installed on your computer.

Use `composer server db exec -- "<command>"` to execute and output the results of an arbitrary SQL command:

```sh
composer server db exec -- 'select id,post_title from wordpress.wp_posts limit 2;'
```
