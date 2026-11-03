# Using Afterburner

If your project uses Afterburner in Altis Cloud, you can enable it in Altis Local Service by setting
the `altis.modules.local-server.afterburner` value to `true`:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "local-server": {
                    "afterburner": true
                }
            }
        }
    }
}
```

Optionally you can specify the version of Afterburner by setting `altis.modules.local-server.afterburner` to either `0.5` or `1.0`.

Afterburner is only enabled on PHP versions later than 7.4.
