# Snicco enterprise WordPress solutions

[![codecov](https://codecov.io/gh/snicco/enterprise/branch/master/graph/badge.svg)](https://codecov.io/gh/snicco/enterprise)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/enterprise/coverage.svg?)](https://shepherd.dev/github/snicco/enterprise)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

## Setup:

The following software is needed on your local machine:

- [make](https://www.gnu.org/software/make/) (probably installed by default)
- [docker](https://docs.docker.com/get-docker/)
- [mkcert](https://github.com/FiloSottile/mkcert)

```shell
make # Displays all commands
make dev-server # Setup the dev environment
```

You should now be able to access a new WordPress installation with all Monorepo plugins at
https://snicco-enterprise.test

Username: `admin`
Password: `admin`

### Customize the environment

Running `make dev-server` will create a WP installation with version 6.0 and PHP7.4.

These settings (and many more) can be customized by passing arguments to make:

- Runtime: `make dev-server WP_VERSION=5.9.3 PHP_VERSION=8.0`
