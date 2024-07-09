# Node.js

Altis supports running Node.js applications alongside WordPress, utilizing WordPress as a headless API.

## Enabling Node.js in Local Server

Node.js can be enabled in Local Server by adding `extra.altis.modules.local-server.nodejs` in the project's `composer.json`

```json
{
	"extra": {
		"altis": {
			"modules": {
				"local-server": {
					"nodejs": {
            "path": "../altis-nodejs-skeleton",
            "version": "21.1"
          }
				}
			}
		}
	}
}
```

`path` refers to the relative path of the project's front-end code.
`version` refers to the version of Node.js to use

This will make the application available at nodejs-my-project.altis.dev.
