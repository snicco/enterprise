ARG WP_VERSION
ARG PHP_VERSION
ARG WP_CLI_VERSION
ARG ALPINE_VERSION

#
# =================================================================
# Install the WordPress base image
# =================================================================
#
# The official docker WordPress image handles volumes and state
# in a way that is not suitable for the development of our
# Monorepo. Once the image is first built the WordPress site
# is expected to be updated through the admin UI.
# This is obviously not what we want as we need to be able
# to switch between PHP and WP versions frequently during
# development and testing.
#
# @see https://github.com/docker-library/wordpress/issues/567
#
# We will instead leverage the official WordPress image
# as a convenient way to get the WordPress source files.
#
FROM wordpress:${WP_VERSION} as wordpress

#
# =================================================================
# Install the wp-cli base image
# =================================================================
#
# Again the official WP-Cli image does not play nicely with our
# setup for the same reasons as the WordPress image so we just
# use the WP-CLI binary that it provides.
#
FROM wordpress:cli-${WP_CLI_VERSION}-php${PHP_VERSION} as wp_cli

#
# =================================================================
# Install PHP-FPM
# =================================================================
#
FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} as php_fpm

#
# =================================================================
# Install system dependencies
# =================================================================
#
# WP-CLI needs less and bash to work properly.
#
RUN apk update && \
    apk add --no-cache less && \
    apk add --no-cache bash

#
# =================================================================
# Install required PHP extensions for WordPress
# =================================================================
#
# We dont use the defaul docker-php-ext-install commands
# because they do not handle installing system requirements for us.
#
# @see https://de.wordpress.org/about/requirements/
# @todo: Install bcmath extension. Currently breaking with install-php-extensions command.
#
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod a+x /usr/local/bin/install-php-extensions && \
    install-php-extensions  json \
                            mysqli \
                            curl \
                            dom \
                            exif \
                            fileinfo \
                            hash \
                            imagick \
                            mbstring \
                            openssl \
                            pcre \
                            xml \
                            zip \
                            filter \
                            iconv \
                            intl \
                            simplexml \
                            sodium \
                            xmlreader \
                            zlib

#
# =================================================================
# Copy WordPress source code and WP-CLI binary
# =================================================================
#
# We leverage docker multi stage builds here to copy just what
# we need from the default WordPress image.
#
# We copy the WordPress source files to /tmp/wordpress.
# This directory will be used in our custom entrypoint
# to populate "WP_APPLICATION_PATH" and "WP_SRC_PATH"
# with new WordPress source files.
#
# These two directories are supplied in the docker-compose.yml
# file and are used to mount named volumes.
#
# We need to overwrite these named volumes each time this
# container is run. Otherwise we will not be able to swithc
# between different WordPress versions.
#
ARG WP_SRC_PATH
ARG WP_APPLICATION_PATH
ARG WP_TMP_PATH=/tmp/wordpress

COPY --from=wordpress /usr/src/wordpress $WP_TMP_PATH
COPY --from=wp_cli /usr/local/bin/wp /usr/local/bin/wp

RUN rm -rf $WP_TMP_PATH/wp-content/plugins/akismet && \
    rm -rf $WP_TMP_PATH/wp-content/plugins/hello.php && \
    rm -rf $WP_TMP_PATH/wp-content/themes/twentytwenty && \
    rm -rf $WP_TMP_PATH/wp-content/themes/twentytwentyone

#
# =================================================================
# Copy our custom entrypoint script
# =================================================================
#
# We need to copy our custom entrypoint script into the container.
# Make sure to reference it based on the full path (from the repo root)
# because this image has the entire monorepo has build context.
#
COPY ./.docker/images/wp/entrypoint.sh /etc/entrypoint.sh

#
# =================================================================
# Create user group and file permissions.
# =================================================================
#
# Its a good practive to chain all RUN commands in order
# to reduce docker image layers.
#
ARG APP_USER_ID
ARG APP_GROUP_ID
ARG APP_USER_NAME
ARG APP_GROUP_NAME

RUN addgroup -g $APP_GROUP_ID $APP_GROUP_NAME && \
    adduser -D -u $APP_USER_ID -s /bin/bash $APP_USER_NAME -G $APP_GROUP_NAME && \
    mkdir -p $WP_APPLICATION_PATH $WP_SRC_PATH && \
    chown -R $APP_USER_NAME: $WP_APPLICATION_PATH && \
    chown -R $APP_USER_NAME: $WP_SRC_PATH && \
    chown -R $APP_USER_NAME: $WP_TMP_PATH && \
    chmod +x /usr/local/bin/wp

#
# =================================================================
# Set the current user in the PHP-FPM config
# =================================================================
#
# For now, instead of using a custom PHP-FPM file we just replace
# the values in the default configuration file.
#
RUN sed -i "s/user = www-data/user = ${APP_USER_NAME}/g" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/group = www-data/group = ${APP_GROUP_NAME}/g" /usr/local/etc/php-fpm.d/www.conf

#
# =================================================================
# Copy custom MU-Plugins
# =================================================================
#
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./.docker/images/wp/mu-plugins $WP_APPLICATION_PATH/wp-content/mu-plugins

#
# =================================================================
# Copy custom wp-config.php
# =================================================================
#
# We cant use the upstream wp-config from dockerhub
# because it does not allow us to change databases dynamically
# at runtime.
#
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./.docker/images/wp/custom-wp-config.php $WP_APPLICATION_PATH/wp-config.php

#
# =================================================================
# Copy PHP source files
# =================================================================
#
# We copy the bundle and component directory so that
# composer symlinks keep working.
#
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./src/Snicco/plugin $WP_APPLICATION_PATH/wp-content/plugins
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./src/Snicco/component $WP_APPLICATION_PATH/wp-content/component
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./src/Snicco/bundle $WP_APPLICATION_PATH/wp-content/bundle

#
# =================================================================
# Expose environment variables
# =================================================================
#
# We need to transform docker build args to env variables.
# The /etc/entrypoint.sh entrypoint needs these variables
# and ENV vars are the only ways to give access to them outside
# the dockerfile.
#
ENV WP_TMP_PATH=$WP_TMP_PATH
ENV WP_SRC_PATH=$WP_SRC_PATH
ENV WP_APPLICATION_PATH=$WP_APPLICATION_PATH

WORKDIR $WP_APPLICATION_PATH

USER $APP_USER_NAME

ENTRYPOINT ["sh", "/etc/entrypoint.sh"]

CMD ["php-fpm", "-F"]

FROM php_fpm as local

#
# =================================================================
# Switch to root user
# =================================================================
#
# At this stage we are not running as root anymore but we need
# root privalages to install apk packages that we want during
# development
#
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
        sudo \
        vim \
        nano

RUN install-php-extensions xdebug \
    # ensure that xdebug is not enabled by default
    && rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY ./.docker/images/zz-app.ini /usr/local/etc/php/conf.d/zz-app.ini

# make bash default shell
RUN sed -e 's;/bin/ash$;/bin/bash;g' -i /etc/passwd

USER $APP_USER_NAME

FROM php_fpm as ci