#
# =================================================================
# CLI PHP Application
# =================================================================
#
# This container runs our application locally and is used
# among other things to run tests, qa-tools, phpstorm, etc.
#
ARG PHP_VERSION
ARG ALPINE_VERSION
ARG COMPOSER_VERSION

FROM composer:${COMPOSER_VERSION} as composer

FROM php:${PHP_VERSION}-cli-alpine${ALPINE_VERSION} as base

ARG APP_USER_ID
ARG APP_GROUP_ID
ARG APP_USER_NAME
ARG APP_GROUP_NAME
ARG APP_CODE_PATH
ARG ENV=${ENV}
ARG APP_CODE_PATH=${APP_CODE_PATH}

#
# =================================================================
# Install additional PHP extensions
# =================================================================
#
# Install PHP extensions that are needed for codeception.
# Loosly based on
# @seehttps://github.com/Codeception/Codeception/blob/5.0/Dockerfile
#
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod a+x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_mysql mysqli bcmath zip

#
# =================================================================
# Create user groups in docker container
# =================================================================
#

RUN addgroup -g $APP_GROUP_ID $APP_GROUP_NAME && \
    adduser -D -u $APP_USER_ID -s /bin/bash $APP_USER_NAME -G $APP_GROUP_NAME && \
    mkdir -p $APP_USER_ID && \
    chown $APP_USER_NAME: $APP_USER_ID

#
# =================================================================
# Copy PHP .ini configuration
# =================================================================
#
# PHP .ini files are loaded alphabetically.
# We prefix our files with "zz" to ensure that they
# overrite the native .ini config
#
COPY ./images/php/conf.d/zz-app.ini /usr/local/etc/php/conf.d
COPY ./images/php/conf.d/zz-app-${ENV}.ini /usr/local/etc/php/conf.d

WORKDIR $APP_CODE_PATH

FROM base as local

ARG APP_CODE_PATH

WORKDIR $APP_CODE_PATH