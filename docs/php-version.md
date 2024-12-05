# PHP Version

In some cases an Altis version may provide support for multiple versions of PHP. This can be useful for testing and developing while
updating an application to work on the latest available version of PHP.

To change the PHP version use the Local Server module configuration to set the `php` property:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "local-server": {
                    "php": "8.2"
                }
            }
        }
    }
}
```

**Note**: Only the major and minor versions (x.y) should be listed. We will use the latest patch version we have tested and made
available both locally and in the cloud.

Altis will always default to the _highest_ supported version of PHP unless support is marked as
experimental. [See the PHP compatibility chart here](docs://guides/updating-php/README.md) to see which version of PHP your version
of Local Server will be running on by default, and which other versions are available.
