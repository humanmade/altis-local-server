# Troubleshooting

## Getting the Local Server status

To get details on the running Local Server status and containers, run `composer server status`. You should see output similar to
this (some columns have been removed for brevity):

```sh
NAME             COMMAND                  SERVICE           STATUS                    PORTS
-----------------------------------------------------------------------------------------------------
site-cavalcade   "/usr/local/bin/cava…"   cavalcade         Up 11 minutes             9000/tcp
site-db          "docker-entrypoint.s…"   db                Up 11 minutes (healthy)   33060/tcp, 0.0.0.0:32930->3306/tcp
site-es          "/usr/share/elastics…"   elasticsearch     Up 11 minutes (healthy)   9300/tcp, 0.0.0.0:32932->9200/tcp
site-kibana      "/usr/local/bin/kiba…"   kibana            Up 11 minutes             0.0.0.0:32935->5601/tcp
site-mailhog     "MailHog"                mailhog           Up 11 minutes             0.0.0.0:32933->1025/tcp
site-nginx       "/docker-entrypoint.…"   nginx             Up 11 minutes             80/tcp, 0.0.0.0:32937->8080/tcp
site-php         "/docker-entrypoint.…"   php               Up 11 minutes             9000/tcp
site-redis       "docker-entrypoint.s…"   redis             Up 11 minutes             0.0.0.0:32927->6379/tcp
site-s3          "/usr/bin/docker-ent…"   s3                Up 11 minutes (healthy)   0.0.0.0:32924->9000/tcp
site-s3-sync     "/bin/sh -c 'mc mb -…"   s3-sync-to-host   Up 11 minutes
site-tachyon     "docker-entrypoint.s…"   tachyon           Up 11 minutes             0.0.0.0:32929->8080/tcp
site-xray        "/xray -t 0.0.0.0:20…"   xray              Up 11 minutes             2000/udp, 0.0.0.0:32931->2000/tcp
```

All containers should have a status of "Up". If they do not, you can inspect the logs for each service by
running `composer server logs <service>`, for example, if `site-db` shows a status other than "Up", run `composer server logs db`.

## Services keep stopping

By default, docker machine sets a default memory limit of 2GB for all of your containers. Because of this if your system becomes too
busy or you're running multiple instances of local server it is recommended to increase this limit to at least 4GB.

In the docker GUI go to the "Preferences" pane, then the "Advanced" tab and move the memory slider to increase memory.

![Docker Advanced Settings](./assets/docker-gui-advanced.png)

## Elasticsearch service fails to start

Elasticsearch requires more memory on certain operating systems such as Ubuntu or when using Continuous Integration services. If
Elasticsearch does not have enough memory it can cause other services to stop working. Local Server supports an environment
variable which can change the default memory limit for Elasticsearch called `ES_MEM_LIMIT`.

You can set the `ES_MEM_LIMIT` variable in 2 ways:

- Set it globally e.g. `export ES_MEM_LIMIT=2g`
- Set it for the local server process only: `ES_MEM_LIMIT=2g composer server start`

Another problem can be related to the Docker Virtual Machine settings. In Linux environments the Elasticsearch container is in
production mode and requires the setting `vm.max_map_count` to be increased. To do this edit the file `/etc/sysctl.conf` and add the
following line:

```text
vm.max_map_count=262144
```

You can also apply the setting live using the following command:

```shell
sysctl -w vm.max_map_count=262144
```

## Port 8080 already in use

Local Server uses [Traefik Proxy](https://doc.traefik.io/traefik/) to listen for requests and map them to the appropriate
containers.

The proxy container runs on ports `80`, `8080` and `443` locally. This means if you are already running a service that uses any of
those ports such as a built-in Apache or nginx server you will need to stop those before you can start Local Server.

On MacOS try running `sudo apachectl stop`. To prevent the built in server from starting automatically when starting the Mac
run `sudo launchctl load -w /System/Library/LaunchDaemons/org.apache.httpd.plist`.

Conversely if you are trying to run another service but are encountering this problem you may need to stop Local Server fully.

To do this run `composer server stop --clean`, or `composer server destroy --clean`. Note that you should only do this if you have
no other running instance of Local Server.

## Windows 11

Docker Desktop for Windows can use either Windows-native Hyper-V virtualization and networking, or can
use [WSL 2](https://learn.microsoft.com/en-us/windows/wsl/install) (Windows Subsystem for Linux, version 2). Altis Local Server
can run in either case. However, consider it may be easier for other build tools used to construct your site if you use WSL 2.

## File sharing is too slow

When using Windows or MacOS on projects with a lot of files such as a `node_modules` directory, or a lot of file churn such as
frequently changing statically built files, the containers can experience a delay in receiving the updated files. This can make
development cumbersome.

The first thing you should try is to switch off the gRPC file sharing option if enabled in the Docker Desktop preferences. If that
doesn't help improve the speed then you can try the Mutagen file sharing option.

See the [Mutagen file sharing set up guide for more details](./mutagen-file-sharing.md).

If you are using an Apple Mac with an Apple Silicon chip, you may no longer need to use the Mutagen file sharing option.
