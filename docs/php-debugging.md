# Debugging PHP

Bugs are an inevitability of writing code and so Local Server provides an easy way to enable [XDebug](https://xdebug.org/) when you need it.

XDebug is _not enabled by default_ because it slows PHP down considerably. This could make you less productive depending on the work you're doing.

## Activating XDebug

When you need to run a debug client you can append the option `--xdebug` to the start command:

```
composer local-server start --xdebug
```

**Note**: you do not need to stop the server first to run the above command.

## Deactivating XDebug

You can start the server again without the `--xdebug` option at any time to deactivate XDebug:

```
composer local-server start
```

## Connecting to XDebug

Most modern editors will have a built in debug client. Instructions for the following are provided:

- VSCode
- PHPStorm
- Atom
- Sublime

XDebug is configured to connect to the default port 9000 so there should be a minimum of configuration required in your editor.

### VSCode



### PHPStorm
