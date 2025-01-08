# Default Site URL

Altis supports specifying a default site URL that can be different from the root URL of the local environment. This is particularly useful in scenarios where the default site resides at a subpath rather than the root of the local URL. By configuring this option, users can ensure WP-CLI commands operate on the correct site by default.

You can define the default site URL in your project’s `composer.json` file under the `extra.altis.modules.local-server.default-site-url` property. Here’s an example:


```json
{
    "extra": {
        "altis": {
            "modules": {
                "local-server": {
                    "default-site-url": "my-site.altis.dev/en/"
                }
            }
        }
    }
}
```

Note: By setting `default-site-url`, the default site URL will only be overridden for WP-CLI commands. It does not affect other components or tools in your environment.
