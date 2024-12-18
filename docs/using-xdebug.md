# Using Xdebug

Bugs are an inevitability of writing code and so Local Server provides an easy way to enable [Xdebug](https://xdebug.org/) when you
need it.

Xdebug is installed but _not enabled by default_ because it can slow PHP down considerably. This could make you less productive
depending on the work you're doing.

## Activating Xdebug

When you need to run your debug client you can append the option `--xdebug` to the start command:

```shell
composer server --xdebug
```

**Note**: you do not need to stop the server first to run the above command.

## Deactivating Xdebug

You can start the server again without the `--xdebug` option at any time to deactivate Xdebug:

```shell
composer server
```

Note that the Xdebug extension will still be installed but it will no longer be active.

## Setting the Xdebug Mode

The Xdebug mode settings allows you to change what Xdebug does for a given session. This defaults to `debug` for step-debugging but
can be configured on start up by passing a value to the `--xdebug` flag like so:

```shell
composer server --xdebug=trace
```

The different modes available are:

- `develop`\
  Enables Development Aids including the overloaded `var_dump()`.
- `coverage`\
  Enables Code Coverage Analysis to generate code coverage reports, mainly in combination with PHPUnit.
- `debug`\
  Enables Step Debugging. This can be used to step through your code while it is running, and analyse values of variables.
- `gcstats`\
  Enables Garbage Collection Statistics to collect statistics about PHP's Garbage Collection Mechanism.
- `profile`\
  Enables Profiling, with which you can analyse performance bottlenecks with tools like KCachegrind.
- `trace`\
  Enables the Function Trace feature, which allows you record every function call, including arguments, variable assignment, and
  return value that is made during a request to a file.

You can enable multiple modes at the same time by comma separating their identifiers as the value of `--xdebug` for
example `--xdebug=develop,trace`.

## Connecting to Xdebug

Most modern editors will have a built in debug client. Instructions for the following editors are below:

- [VS Code](#vs-code)
- [PhpStorm](#phpstorm)

Xdebug is configured to connect to the default port 9003 so there should be a minimum of configuration required in your editor.

### VS Code

1. Install a [PHP Debug extension](https://github.com/xdebug/vscode-php-debug)
2. Open the debug tab (the bug icon on the menu sidebar).
3. In the dropdown menu at the top of the left hand side bar choose "Add configuration".
4. In the popup that appears select "PHP" as your environment.
5. You will be taken a new file called `.vscode/launch.json` with the default settings:

    ```json
    {
      "version": "0.2.0",
      "configurations": [
        {
          "name": "Listen for Xdebug",
          "type": "php",
          "request": "launch",
          "port": 9003
        },
        {
          "name": "Launch currently open script",
          "type": "php",
          "request": "launch",
          "program": "${file}",
          "cwd": "${fileDirname}",
          "port": 9003
        }
      ]
    }
    ```

6. Add the following `hostname` and `pathMappings` property to each configuration:

    ```json
    {
        "version": "0.2.0",
        "configurations": [
            {
                "name": "Listen for Xdebug",
                "type": "php",
                "request": "launch",
                "port": 9003,
                "hostname": "0.0.0.0",
                "pathMappings": {
                    "/usr/src/app": "${workspaceRoot}"
                }
            },
            {
                "name": "Launch currently open script",
                "type": "php",
                "request": "launch",
                "program": "${file}",
                "cwd": "${fileDirname}",
                "port": 9003,
                "hostname": "0.0.0.0",
                "pathMappings": {
                    "/usr/src/app": "${workspaceRoot}"
                }
            }
        ]
    }
    ```

7. You are done, click the green play button to start the debug client.

For more information on the available configuration options, including Xdebug
settings, [view the VS Code Debugging documentation here](https://go.microsoft.com/fwlink/?linkid=830387).

### PhpStorm

Local Server takes advantage of
PhpStorm's [Zero Configuration Debugging](https://www.jetbrains.com/help/phpstorm/zero-configuration-debugging.html). All you need
to do is tell it about the server by following these steps:

1. Go to `Preferences > PHP > Servers`.
2. Click the plus icon and create a new Server entry with the following settings:
    - The name should be your project host name: `<my-project>.altis.dev`
    - The host name should also be: `<my-project>.altis.dev`
    - Port: `443`
    - Check the "Use path mappings" box
    - Next to your project's root directory enter `/usr/src/app`  
        ![Example PhpStorm Configuration](./assets/phpstorm-config.png)
3. Set some breakpoints and click the "Listen for Debug Connections" icon  
    ![PhpStorm Debug Icon](./assets/phpstorm-start-debug.png)

## Accessing Xdebug output

If you are starting Xdebug with any the following modes you will want to access their output in the PHP container's `/tmp`
directory.

This is achievable in 2 ways:

1. Use `composer server shell` to access the container and look at the files using `cat`, `less` or other file reader
2. Pass the `--tmp` flag when starting Local Server to mount the `/tmp` directory to `.tmp` in your project root

The second option using `--tmp` has the advantage of allowing you to easily open the output files in external programs that
understand them such as [KCachegrind](https://kcachegrind.github.io/).

Note that you should add `.tmp` to your project's `.gitignore` file if you use this option.

## Profiling With WebGrind

In most cases the X-Ray traces in the Query Monitor developer tools panel will give a good indication of any bottlenecks in your
code however those traces are not available when running CLI commands or background cron tasks.

If Xdebug is activated in profiling mode using `--xdebug=profile` on start up it will generate Cachegrind files in the `/tmp`
directory.

You can use the `--tmp` option to mount and view these files in a program like KCachegrind or QCacheGrind however Local Server
provides a web interface for viewing the profiles. This is set up automatically if starting the server with `--xdebug=profile`.

In your browser go to `/webgrind/` on your project domain, for example `https://my-project.altis.dev/webgrind/` to access the UI for
viewing
