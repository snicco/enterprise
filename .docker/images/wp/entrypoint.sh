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
tar cf - -C "$WP_TMP_PATH" . | tar xpf - -C "$WP_APPLICATION_PATH"
echo "Copied fresh WordPress files from $WP_TMP_PATH to $WP_APPLICATION_PATH"

tar cf - -C "$WP_TMP_PATH" . | tar xpf - -C "$WP_SRC_PATH"
echo "Copied fresh WordPress files from $WP_TMP_PATH to $WP_SRC_PATH"

rm -rf "$WP_TMP_PATH"
echo "Removed $WP_TMP_PATH"

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
  wp --allow-root core install --url="$WORDPRESS_SITE_URL" --title="Snicco Enterprise" --admin_user=admin --admin_password=admin --admin_email=admin@test.com
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