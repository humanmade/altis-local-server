## DNS Proxy Installation

Let's install some dependencies! The following script will install `dnsmasq` and configure a `.hmdocker` domain locally:

```
bin/install.sh
```

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

## Local DNS

The proxy will monitor Docker for containers and map their localhost:port combination to a DNS address, formulated as `{service}.{project}.{domain}`. Traefik is configured to use `.hmdocker` as the `{domain}`. By default, Docker Compose will use the name of the directory the `docker-compose.yml` file lives in for the name of the Project, which gets mapped to `{project}`. If this repo was cloned as a directory named `base`, the `nginx` service's domain Traefik generates would be `nginx.base.hmdocker`. It's also possible to tell Traefik to use another domain name, via `label`s:

```yaml
# Abreviated:
services:
  nginx:
    labels:
      - "traefik.frontend.rule=Host:${COMPOSE_PROJECT_NAME:-default}.hmdocker"
```

We can now pass the `COMPOSE_PROJECT_NAME` environment variable when running `docker-compose up`, and it will use that value as the name of the domain. The above example will use `COMPOSE_PROJECT_NAME` for the name of the domain, or `default` if none is passed. For instance, if we pass `COMPOSE_PROJECT_NAME=foobar`, the domain Traefik uses is `foobar.hmdocker`.

