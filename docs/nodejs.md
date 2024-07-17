# Node.js

Altis supports running Node.js applications alongside WordPress, utilizing WordPress as a headless API.

## Enabling Node.js in Local Server

Node.js can be enabled in Local Server by adding `extra.altis.modules.local-server.nodejs` in the project's `composer.json`

```json
{
   "extra":{
      "altis":{
         "modules":{
            "local-server":{
               "nodejs":{
                  "path":"../altis-nodejs-skeleton"
               }
            }
         }
      }
   }
}
```

`path` is relative to the directory where `composer.json` lives.

## Setting Node.js Version
Similar to configuring the Altis infrastructure, the Local Server determines the Node.js version to use based on the `engines.node` value found in the `package.json` at the specified `path`.

## Running Development Server
Once configured, the Local Server executes `npm run dev` inside the Node.js container at the specified path. This command watches for changes and recompiles necessary files.

## Accessing the Application
This setup makes the application accessible at `https://nodejs-{project-name}.altis.dev`.
