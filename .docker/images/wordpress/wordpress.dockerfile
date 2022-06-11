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
# setup as is also relies on a shared /var/www/html volume
# between all containers.
#
# This just gets in our way.
#
FROM wordpress:cli-${WP_CLI_VERSION}-php${PHP_VERSION} as wp_cli

#
# =================================================================
# Install PHP-FPM and NGINX
# =================================================================
#
# We run PHP-FPM and NGINX in the same container because
# there is no easy way to share the code between separate
# PHP and NGINX containers.
#
# For our needs this is perfectly fine as our main goals
# with the docker setup are development flexibility and testability.
#
FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} as php_nginx

ARG APP_USER_ID
ARG APP_GROUP_ID
ARG APP_USER_NAME
ARG APP_GROUP_NAME
ARG APP_CODE_PATH
ARG NGINX_VERSION

# @todo use NGINX_VERSION to pin nginx. Setting a version breaks apk.
RUN apk update && \
    apk add --no-cache nginx && \
    apk add --no-cache openrc && \
    apk add --no-cache less && \
    apk add --no-cache bash

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
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod a+x /usr/local/bin/install-php-extensions && \
    install-php-extensions json mysqli curl dom exif fileinfo hash imagick mbstring openssl pcre xml zip filter iconv intl simplexml sodium xmlreader zlib

#
# =================================================================
# Copy WordPress source code and WP-CLI binary
# =================================================================
#
COPY --from=wordpress /usr/src/wordpress $APP_CODE_PATH
COPY --from=wp_cli /usr/local/bin/wp /usr/local/bin/wp

#
# =================================================================
# Create user group in docker container and remove WP bloat
# =================================================================
#
# Its a good practive to chain all RUN commands in order
# to reduce docker image layers.
#
RUN addgroup -g $APP_GROUP_ID $APP_GROUP_NAME && \
    adduser -D -u $APP_USER_ID -s /bin/bash $APP_USER_NAME -G $APP_GROUP_NAME && \
    mkdir -p $APP_CODE_PATH && \
    chown -R $APP_USER_NAME: $APP_CODE_PATH && \
    chmod +x /usr/local/bin/wp && \
    mv $APP_CODE_PATH/wp-config-docker.php $APP_CODE_PATH/wp-config.php && \
    rm -rf $APP_CODE_PATH/wp-content/plugins/akismet && \
    rm -rf $APP_CODE_PATH/wp-content/plugins/hello.php && \
    rm -rf $APP_CODE_PATH/wp-content/themes/twentytwenty && \
    rm -rf $APP_CODE_PATH/wp-content/themes/twentytwentyone

#
# =================================================================
# Set the current user in the PHP-FPM config
# =================================================================
#
# Instead of using a custom PHP-FPM file we just replace
# the values in the default configuration file.
#
RUN sed -i "s/user = www-data/user = ${APP_USER_NAME}/g" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/group = www-data/group = ${APP_GROUP_NAME}/g" /usr/local/etc/php-fpm.d/www.conf

COPY ./.docker/images/wordpress/entrypoint.sh /etc/entrypoint.sh

#
# =================================================================
# Copy PHP source files
# =================================================================
#
COPY ./src/Snicco/plugin $APP_CODE_PATH/wp-content/plugins
COPY ./src/Snicco/component $APP_CODE_PATH/wp-content/component
COPY ./src/Snicco/bundle $APP_CODE_PATH/wp-content/bundle

COPY ./.docker/images/wordpress/nginx/certs /etc/nginx/certs/self-signed
# Default nginx installed via apk has looks for the config in http.d not conf.d
COPY ./.docker/images/wordpress/nginx/default.conf /etc/nginx/http.d/default.conf

RUN sed -i "s#root __NGINX_ROOT;#root $APP_CODE_PATH;#" /etc/nginx/http.d/default.conf
RUN sed -i "s#user nginx;;#user $APP_USER_NAME;#" /etc/nginx/nginx.conf

RUN mkdir -p /var/log/nginx /var/cache/nginx /var/lib/nginx/tmp /var/lib/nginx/logs && \
    chown -R $APP_USER_NAME: /var/cache/nginx && \
    chown -R $APP_USER_NAME: /var/log/nginx && \
    chown -R $APP_USER_NAME: /etc/nginx/certs && \
    chown -R $APP_USER_NAME: /var/lib/nginx && \
    chown -R $APP_USER_NAME: /run/nginx

WORKDIR $APP_CODE_PATH

USER $APP_USER_NAME

ENTRYPOINT ["sh", "/etc/entrypoint.sh"]

FROM php_nginx as local

