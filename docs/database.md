# Interacting with the Database

You can log into MySQL and run any query in the default database (`wordpress`) by using:

```sh
composer server db
```

Use `composer server db info` to retrieve MySQL info, connection details and forwarded ports:

```sh
Root password:  wordpress

Database:       wordpress
User:           wordpress
Password:       wordpress

Version:        5.7.26-1debian9
Host:           0.0.0.0
Port:           32775
```

Use `composer server db sequel` to generate an SPF file. This command can only be run under MacOS and requires [Sequel Pro](https://www.sequelpro.com/) to be installed in your computer. 
