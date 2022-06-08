ARG COMPOSER_VERSION=2.3.7
ARG PHP_VERSION

FROM composer:${COMPOSER_VERSION} as composer

FROM php:${PHP_VERSION}-cli-alpine

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN chmod a+x /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN composer global require rector/rector:0.12.18 \
    && composer global require symplify/easy-coding-standard:10.1.2

WORKING_DIR project

CMD ~/.composer/vendor/bin/rector

