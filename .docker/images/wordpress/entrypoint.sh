#!/usr/bin/env bash
set -e

#
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
    wp --allow-root rewrite structure '/%postname%/' --hard
fi

php-fpm -D
nginx -g 'daemon off;'