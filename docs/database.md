# Interacting with the Database

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
Port:           32775

Version:        5.7.26-1debian9
```

Use `composer server db sequel` to open the database in Sequel Pro. This command can only be run under MacOS and requires [Sequel Pro](https://www.sequelpro.com/) to be installed on your computer.
