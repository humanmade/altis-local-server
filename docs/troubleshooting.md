# Troubleshooting

## Getting the Local Server status

To get details on the running Local Server status and containers, run `composer server status`. You should see output similar to:

```sh
        Name                        Command               State                  Ports
-----------------------------------------------------------------------------------------------------
my-site_cavalcade_1       /usr/local/bin/cavalcade         Up
my-site_db_1              docker-entrypoint.sh mysqld      Up       0.0.0.0:32818->3306/tcp
my-site_elasticsearch_1   /elastic-entrypoint.sh ela ...   Up       0.0.0.0:32821->9200/tcp, 9300/tcp
my-site_nginx_1           nginx -g daemon off;             Up       80/tcp, 0.0.0.0:32823->8080/tcp
my-site_php_1             /docker-entrypoint.sh php-fpm    Up       0.0.0.0:32822->9000/tcp
my-site_redis_1           docker-entrypoint.sh redis ...   Up       0.0.0.0:32820->6379/tcp
my-site_s3_1              fakes3 server --root . --p ...   Up       0.0.0.0:32819->8000/tcp
my-site_tachyon_1         node server.js --debug           Up       0.0.0.0:8081->8080/tcp
my-site_xray_1            /usr/bin/xray -b 0.0.0.0:2000    Up       0.0.0.0:32817->2000/tcp, 2000/udp
```

All containers should have a status of "Up". If they do not, you can inspect the logs for each service by running `composer server logs <service>`, for example, if `site_db_1` shows a status other than "Up", run `composer server logs db`.

## Services keep stopping

By default docker machine sets a default memory limit of 2GB for all of your containers. Because of this if your system becomes too busy or you're running multiple instances of local server it is recommended to increase this limit to at least 4GB.

In the docker GUI go to the "Preferences" pane, then the "Advanced" tab and move the memory slider up.

![Docker Advanced Settings](./assets/docker-gui-advanced.png)

## ElasticSearch service fails to start

ElasticSearch requires more memory on certain operating systems such as Ubuntu or when using Continuous Integration services. If ElasticSearch does not have enough memory it can cause other services to stop working. The Local Server supports an environment variable which can change the default memory limit for ElasticSearch called `ES_MEM_LIMIT`.

You can set the `ES_MEM_LIMIT` variable in 2 ways:

- Set it globally eg: `export ES_MEM_LIMIT=2g`
- Set it for the local server process only: `ES_MEM_LIMIT=2g composer server start`

Another problem can be related to the Docker Virtual Machine settings. In Linux environments the ElasticSearch container is in production mode and requires the setting `vm.max_map_count` to be increased. To do this edit the file `/etc/sysctl.conf` and add the following line:

```
vm.max_map_count=262144
```

You can also apply the setting live using the following command:

```
sysctl -w vm.max_map_count=262144
```

## Port 8080 already in use

Local Server uses [Traefik Proxy](https://doc.traefik.io/traefik/) to listen for requests and map them to the appropriate containers.

The proxy container runs on ports `80`, `8080` and `443` locally. This means if you are already running a service that uses any of those ports such as a built in Apache or nginx server you will need to stop those before you can start Local Server.

On MacOS try running `sudo apachectl stop`. To prevent the built in server from starting automatically when starting the Mac run `sudo launchctl load -w /System/Library/LaunchDaemons/org.apache.httpd.plist`.

Conversely if you are trying to run another service but are encoutnering this problem you may need to stop Local Server fully.

To do this run `composer server stop --clean`, or `composer server destroy --clean`. Note that you should only do this if you have no other running instance of Local Server.

## Windows 10 Home Edition

Docker Desktop for Windows uses Windows-native Hyper-V virtualization and networking, which is not available in the Windows 10 Home edition. If you are using Windows 10 Home Edition you will need to use the [Local Chassis](docs://local-chassis) environment.


## File sharing is too slow

When using Windows or MacOS on projects with a lot of files such as a `node_modules` directory, or a lot of file churn such as frequently changing statically built files, the containers can experience a delay in receiving the updated files. This can make development cumbersome.

The first thing you should try is to switch off the gRPC file sharing option if enabled in the Docker Desktop preferences. If that doesn't help improve the speed then you can try the experimental Mutagen file sharing option.

See the [Mutagen file sharing set up guide for more details](./mutagen-file-sharing.md).

## Server fails to start when using Mutagen

Due to the variability of Docker Desktop, Docker Engine and Mutagen versions, and that Mutagen is still in beta, you may encounter some problems.

Currently Mutagen file sharing support has the following pre-requisites in order to function:

- You must be using Docker Compose v2, available with Docker Desktop 4.1.0 and up
  - Check your Docker Desktop preferences as there is a toggle to use Compose v1
- You must have the latest Mutagen Compose Beta installed, or at least 0.13.0-beta3
