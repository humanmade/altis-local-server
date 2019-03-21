## Usage

```
# Let's start the Proxy first
docker-compose -f proxy.yml up -d
VOLUME=/path/to/wordpress/project docker-compose up
```

If you wish to re-use this configuration, you'll need to use the `COMPOSE_PROJECT_NAME` environment variable to change the name of the containers and local domain, otherwise there will be conflicts:

```
VOLUME=/path/to/wordpress/project COMPOSE_PROJECT_NAME=some-project docker-compose up
```

We can now pass the `COMPOSE_PROJECT_NAME` environment variable when running `docker-compose up`, and it will use that value as the name of the domain. The above example will use `COMPOSE_PROJECT_NAME` for the name of the domain, or `default` if none is passed. For instance, if we pass `COMPOSE_PROJECT_NAME=foobar`, the domain Traefik uses is `foobar.altis.dev`.

