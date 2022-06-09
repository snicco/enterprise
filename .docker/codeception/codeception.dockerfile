ARG PHP_VERSION

FROM php:${PHP_VERSION}-cli-alpine

WORKDIR /project

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod a+x /usr/local/bin/install-php-extensions && \
    install-php-extensions xdebug pdo pdo_mysql mysqli bcmath zip

ENTRYPOINT ["vendor/bin/codecept"]
