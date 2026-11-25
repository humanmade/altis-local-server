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

Note: `default-site-url` sets the default site for WP-CLI commands run through Composer, for `wp` inside `composer server shell`, and for any PHP run inside the container without a web request. It does not affect web requests or other components in your environment.

The value is stored in the generated `docker-compose.yml`, so run `composer server start` again after changing it for the change to take effect.
