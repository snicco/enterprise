# Snicco Enterprise
[![codecov](https://codecov.io/gh/snicco/enterprise/branch/master/graph/badge.svg)](https://codecov.io/gh/snicco/enterprise)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/enterprise/coverage.svg?)](https://shepherd.dev/github/snicco/enterprise)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The development monorepo of Snicco's commercial WordPress Software.

## Setup:

This monorepo is 100% managed by make/docker and can be setup locally with only a few commands.

To get started you need on your local machine:

- [make](https://www.gnu.org/software/make/) (Version 4+)
- [docker](https://docs.docker.com/get-docker/)
- [mkcert](https://github.com/FiloSottile/mkcert) (To access the development site with ssl)

PHP, NGINX, Node, etc. are not needed locally.

### Initializing the repository

```shell
make init # Initializes the repository (use once).
```

**On Linux only:** You have to adjust the `APP_USER_XXX` values in [.make/.env](.make/.mk.env.dist#26) so that they match the values on your current machine.

### Creating a dev-server

To start the development environment run:

```shell
make dev-server 
```

You should now be able to log in into a new WordPress installation with all Monorepo plugins at
https://snicco-enterprise.test/wp-login.php using 

 - Username: `admin`
 - Password: `admin`

### Displaying all available commands

```shell
make # or make help
```

### Customize the environment

By default, running `make dev-server` will create a WP installation with version 6.0 and PHP7.4.

These settings (and many more) can be customized by passing arguments to make:

- Runtime: `make dev-server WP_VERSION=5.9.3 PHP_VERSION=8.0`

### Utility functions

For convenience, you can add the contents of [.docker/bash_profile.sh](.docker/bash_profile.sh) to your bash profile:

This will give you a `din` function that will allow you to open a shell a docker container from anywhere on your machine by running:

```shell
din wp # open a shell in the wp container (must be running)
din nginx # open a shell in the nginx container (must be running)
```

On MacOs:

```shell
cat .docker/bash_profile.sh >> ~/.zshrc
```

On Linux:

```shell
cat .docker/bash_profile.sh >> ~/.bash_profile
```