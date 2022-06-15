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
#FROM wordpress:cli-${WP_CLI_VERSION}-php${PHP_VERSION} as wp_cli

#
# =================================================================
# Install PHP-CLI
# =================================================================
#
FROM php:${PHP_VERSION}-cli-alpine${ALPINE_VERSION} as php_cli

#
# =================================================================
# Install system dependencies
# =================================================================
#
# WP-CLI needs less and bash to work properly.
#
#RUN apk update && \
#    apk add --no-cache less && \
#    apk add --no-cache bash

#
# =================================================================
# Install required PHP extensions for WordPress
# =================================================================
#
# Again, we dont use the defaul docker-php-ext-install commands
# because they do not handle installing system requirements for us.
#
# @see https://de.wordpress.org/about/requirements/
# @todo: Install bcmath extension. Currently breaking with install-php-extensions command.
#
#ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
#RUN chmod a+x /usr/local/bin/install-php-extensions && \
#    install-php-extensions json mysqli curl dom exif fileinfo hash imagick mbstring openssl pcre xml zip filter iconv intl simplexml sodium xmlreader zlib

#
# =================================================================
# Copy WordPress source code and WP-CLI binary
# =================================================================
#
# We leverage docker multi stage builds here to copy just what
# we need.
#
# We will also go ahead and remove bloat from the default
# WordPress image that we dont need.
#
# We also copy the source files to /tmp/wordpress
# so that we can have a separate volume with just the
# default WordPress files.
#
COPY --from=wordpress /usr/src/wordpress /usr/src/wordpress
#COPY --from=wp_cli /usr/local/bin/wp /usr/local/bin/wp

RUN rm -rf /usr/src/wordpress/wp-content/plugins/akismet && \
    rm -rf /usr/src/wordpress/wp-content/plugins/hello.php && \
    rm -rf /usr/src/wordpress/wp-content/themes/twentytwenty && \
    rm -rf /usr/src/wordpress/wp-content/themes/twentytwentyone

#
# =================================================================
# Copy our custom entrypoint script
# =================================================================
#
# We need to copy our custom entrypoint script into the container.
# Make sure to reference it based on the full path (from the repo root)
# because this image has the entire monorepo has build context.
#
COPY ./.docker/images/wordpress_data/entrypoint.sh /etc/entrypoint.sh

#
# =================================================================
# Create user group in docker container
# =================================================================
#
# Its a good practive to chain all RUN commands in order
# to reduce docker image layers.
#
ARG APP_USER_ID
ARG APP_GROUP_ID
ARG APP_USER_NAME
ARG APP_GROUP_NAME
ARG APP_CODE_PATH

RUN addgroup -g $APP_GROUP_ID $APP_GROUP_NAME && \
    adduser -D -u $APP_USER_ID -s /bin/bash $APP_USER_NAME -G $APP_GROUP_NAME && \
    mkdir -p $APP_CODE_PATH $APP_CODE_PATH/wp-content && \
    chown -R $APP_USER_NAME: $APP_CODE_PATH && \
    chown -R $APP_USER_NAME: /usr/src/wordpress

#
# =================================================================
# Set the current user in the PHP-FPM config
# =================================================================
#
# For now, instead of using a custom PHP-FPM file we just replace
# the values in the default configuration file.
#
#RUN #sed -i "s/user = www-data/user = ${APP_USER_NAME}/g" /usr/local/etc/php-fpm.d/www.conf && \
#    sed -i "s/group = www-data/group = ${APP_GROUP_NAME}/g" /usr/local/etc/php-fpm.d/www.conf

USER $APP_USER_NAME

#
# =================================================================
# Copy custom MU-Plugins
# =================================================================
#
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./.docker/images/wordpress/mu-plugins /usr/src/wordpress/wp-content/mu-plugins
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./.docker/images/wordpress/custom-wp-config.php /usr/src/wordpress/wp-config.php

#
# =================================================================
# Copy PHP source files
# =================================================================
#
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./src/Snicco/plugin /usr/src/wordpress/wp-content/plugins
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./src/Snicco/component /usr/src/wordpress/wp-content/component
COPY --chown=$APP_USER_NAME:$APP_GROUP_NAME ./src/Snicco/bundle /usr/src/wordpress/wp-content/bundle

WORKDIR $APP_CODE_PATH

ENTRYPOINT ["sh", "/etc/entrypoint.sh"]
CMD ["/bin/bash"]

FROM php_cli as local

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

# make bash default shell
RUN sed -e 's;/bin/ash$;/bin/bash;g' -i /etc/passwd

USER $APP_USER_NAME

