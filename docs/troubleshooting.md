# Troubleshooting

## Getting the Local Server status

To get details on the running Local Server status and containers, run `composer local-server status`. You should see output similar to:

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

All containers should have a status of "Up". If they do not, you can inspect the logs for each service by running `composer local-server logs <service>`, for example, if `site_db_1` shows a status other than "Up", run `composer local-server logs db`.
