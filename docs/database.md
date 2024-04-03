# Database

## Available Versions

The available versions for MySQL are `5.7` and `8.0`

MySQL defaults to version 8.0 however you can change the version in your config if your cloud environments have not yet been updated and you need to match them:

```json
{
	"extra": {
		"altis": {
			"modules": {
				"local-server": {
					"mysql": "8.0"
				}
			}
		}
	}
}
```


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
Port:           32775

Version:        5.7.26-1debian9
MySQL link:     mysql://wordpress:wordpress@0.0.0.0:32775/wordpress
```

Use `composer server db sequel` to open the database in Sequel Pro. This command can only be run under MacOS and requires [Sequel Pro](https://www.sequelpro.com/) to be installed on your computer.

Use `composer server db exec -- "<command>"` to execute and output the results of an arbitrary SQL command:

```sh
composer server db exec -- 'select id,post_title from wordpress.wp_posts limit 2;'
```
