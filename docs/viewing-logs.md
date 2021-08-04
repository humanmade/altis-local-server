# Viewing Logs

Often you'll want to access logs from the services that Local Server provides. For example, PHP Errors Logs, Nginx Access Logs, or MySQL Logs. To do so, run the `composer server logs <service>` command. `<service>` can be any of `php`, `nginx`, `db`, `elasticsearch`, `s3`, `xray`, `cavalcade` or `redis`. This command will tail the logs (live update). To exit the log view, press `Ctrl+C`.

Local Server provides these commands as aliases to the underlying `docker log` command, so you may alternatively list out running containers with `docker ps` and then follow the logs for any individual running container. This lets you monitor logs from any working directory without relying on the `composer server` alias. To monitor the logs of the Traefik proxy which routes requests between multiple Local Server instances, for example, you would run `docker logs docker_proxy_1 --follow`.
