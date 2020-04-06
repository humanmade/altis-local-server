<h1 align="center"><img src="https://make.hmn.md/altis/Altis-logo.svg" width="89" alt="Altis" /> Local Server</h1>

<p align="center">Local development server for <strong><a href="https://altis-dxp.com/">Altis</a></strong>.</p>

<p align="center"><a href="https://packagist.org/packages/altis/local-server"><img alt="Packagist Version" src="https://img.shields.io/packagist/v/altis/local-server.svg"></a></p>

## Local Server

A local development environment for Altis projects, built on Docker.

## Dependencies

* [Composer](https://getcomposer.org/download/)
* [Docker Desktop](https://www.docker.com/get-started) (you can [install Docker Machine directly](https://docs.docker.com/machine/install-machine/) if preferred)

## Installation with Altis

Altis Local Server is included by default in an Altis project, so you don't need to install anything else.

## Installation without Altis

Altis Local Server can be installed as a dependency within a Composer-based WordPress project:

`composer require --dev altis/local-server`

## Getting Started

In your Altis project you can run the following commands:

```
# Start the server cluster
composer server start

# Stop the server cluster
composer server stop
```

[For full documentation click here](./docs).
