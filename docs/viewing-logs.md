# Viewing Logs

Often you'll want to access logs from the services that Local Server provides. For example, PHP Errors Logs, Nginx Access Logs, or MySQL Logs. To do so, run the `composer server logs <service>` command. `<service>` can be any of `php`, `nginx`, `db`, `elasticsearch`, `s3`, `xray`, `cavalcade` or `redis`. This command will tail the logs (live update). To exit the log view, press `Ctrl+C`.
