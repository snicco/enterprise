#!/usr/bin/env bash
set -e

#
# =================================================================
# Copy fresh WordPress source files
# =================================================================
#
# Since this container runs with a named volume (APP_CODE_PATH)
# we need to copy the installed WordPress files in entrypoint
# script to ensure that everything stays up to date.
#
# We can't run this code in the Dockerfile since it would
# only apply the first time the image is build.
# Subsequent builds would always use stale WordPress code
# and stuff like updating the WordPress version would not work
# properly.
#
tar --create --file - --directory /usr/src/wordpress . | tar --extract --file -
rm ./wp-config-docker.php
rm ./wp-config-sample.php

# =================================================================
# Install WordPress if its not installed already
# =================================================================
#
# We need to install WordPress here so that we have a ready2go
# instance in CI. Otherwise we would always have to install
# WP in the UI first.
#
# This can only be done at container startup since it requires
# database access.
#
if ! wp --allow-root core is-installed; then
  wp --allow-root core install --url="https://snicco-enterprise.test" --title="Snicco Enterprise" --admin_user=admin --admin_password=admin --admin_email=admin@test.com
  # Permalink structure
  wp --allow-root rewrite structure '/%postname%' --hard
fi

#
# =================================================================
# Run container
# =================================================================
#
# The value of "$@" is [php-fpm, -F] by default. But by not
# hard-coding this here we leave ourselves the option to pass
# a different command at runtime.
#
exec "$@"
