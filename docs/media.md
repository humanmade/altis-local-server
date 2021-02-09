# Local Media

Local Server supports the [Altis Media module](docs://media/) by replicating the media handling capabilities of a deployed Altis environment. A new Local Server setup will support [Dynamic Images](docs://media/dynamic-images/) and other media features, and all images you upload will be stored in a local container on your development machine.

When using Local Server to work on an existing Altis site, you may need to pull down a database backup for local testing. Downloading uploaded media along with this database backup may be prohibitively time consuming, or take too much local disk space. Local Server can be configured to request these images directly from an Altis cloud environment.

You can add a file `.config/nginx-additions-media.conf` to your Altis project which instructs Local Server to redirect image requests to a remote server if they were not accessible locally. This preserves your ability to upload and edit local images, while allowing any image file not present in your environment to be loaded from a remote bucket instead.

Assuming that your Altis project uses the name `myproject` for Local Server and your environment uses the domain name `myproject.altis.cloud`, your `nginx-additions-media.conf` could look like this:

<pre><code>
# Listen for Tachyon 404s, and try redirecting those requests to the development S3 bucket.
location ~* ^/tachyon.+\.(jpe?g|gif|png|webp).*$ {
	try_files $uri @imageFallback;
}

location @imageFallback {
	rewrite ^/tachyon/(.*)$ https://myproject.altis.cloud/tachyon/$1 break;
}
</code></pre>

Your production Tachyon URL will be whatever the primary domain name for the stack is plus the path `/tachyon/` so it may not always look like the example above.

## Refreshing Local Media

If you chose to download images to your `uploads/` folder instead of using one of the above remote fallback approaches, you may need to signal Local Server to synchronize your local files with the image service. To run a local image sync, use the command `composer server import-uploads`.