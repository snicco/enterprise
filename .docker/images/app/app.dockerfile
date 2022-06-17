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

#
# =================================================================
# Install additional PHP extensions
# =================================================================
#
# We don't use docker-php-ext-*** commands because
# they dont resolve system dependencies for us.
#
# There is an alternative tool that will handle installing
# all required system dependencies for given PHP extensions
# (@see https://github.com/mlocati/docker-php-extension-installer)
#
# Install PHP extensions that are needed for codeception.
# Loosly based on
# @see https://github.com/Codeception/Codeception/blob/5.0/Dockerfile
#
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod a+x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql mysqli bcmath zip \
                           pcntl posix #Required for psalm threads

#
# =================================================================
# Create user groups and permissions in docker container
# =================================================================
#
# We need to make sure that all directories in the container
# that are targets of bind mounts exist and have the correct
# permissions.
#
# Otherwise we get weird permission issues (
# https://github.com/docker/for-mac/issues/5480
# )
#
ARG APP_USER_ID
ARG APP_GROUP_ID
ARG APP_USER_NAME
ARG APP_GROUP_NAME
ARG MONOREPO_PATH
ARG WORDPRESS_APP_PATH
ARG WORDPRESS_SRC_PATH

RUN addgroup -g $APP_GROUP_ID $APP_GROUP_NAME && \
    adduser -D -u $APP_USER_ID -s /bin/bash $APP_USER_NAME -G $APP_GROUP_NAME && \
    mkdir -p $MONOREPO_PATH $WORDPRESS_APP_PATH $WORDPRESS_SRC_PATH && \
    mkdir -p $WORDPRESS_APP_PATH/wp-content/plugins $WORDPRESS_APP_PATH/wp-content/mu-plugins && \
    chown -R $APP_USER_NAME:$APP_GROUP_NAME $MONOREPO_PATH $WORDPRESS_APP_PATH $WORDPRESS_SRC_PATH

#
# =================================================================
# Copy the composer binary from the official image
# =================================================================
#
# We leverage multi-stage builds here, since we
# are only interested in the composer binary.
#
# Also make sure that composer can write to its cache directory
# since the official image installed composer as root
# but we are not running the container as root.
#
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME --from=composer /usr/bin/composer /usr/local/bin/composer
RUN mkdir -p /home/$APP_USER_NAME/.composer && \
    chown -R $APP_USER_NAME /home/$APP_USER_NAME/.composer

WORKDIR $MONOREPO_PATH

USER $APP_USER_NAME

FROM base as local

USER root

#
# =================================================================
# Install local development tools
# =================================================================
#
# In the local target we can go ahead and install some
# system librariers that make development easier for us.
#
# We dont add them to the base target so that they
# dont end up in the CI stage.
#
RUN apk add --update --no-cache \
        bash \
        make \
        sudo \
        vim

# make bash default shell
RUN sed -e 's;/bin/ash$;/bin/bash;g' -i /etc/passwd

#
# =================================================================
# Shared multi-stage variables
# =================================================================
#
# According to the docs (
#   @see https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
# ), multi-stage variables are only visible to other stages
# if they are defined before the first "FROM" statement. So we need
# to declare them here aswell.
#
ARG APP_USER_NAME

USER $APP_USER_NAME

