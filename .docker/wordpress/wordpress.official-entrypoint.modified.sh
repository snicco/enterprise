#!/usr/bin/env bash

# This is a modified version of: https://github.com/docker-library/wordpress/blob/master/latest/php7.4/fpm-alpine/docker-entrypoint.sh
#
# The modifications made are:
# - removal of apache conditional checks
#
# Basically, the official docker image has no way for us to reuse the "file copy" logic without starting the
# php-fpm process.
# This is problematic for our setup because in CI we need a fully installed WordPress installation.
# For that we need a functioning wp-cli instance. However, we cant use wp-cli before this code runs
# because it will not be a valid WordPress installation. Only the wp-content dir will be present.

set -Eeuo pipefail

uid="$(id -u)"
gid="$(id -g)"
if [ "$uid" = '0' ]; then
  user='www-data'
  group='www-data'
else
  user="$uid"
  group="$gid"
fi

if [ ! -e index.php ] && [ ! -e wp-includes/version.php ]; then
  # if the directory exists and WordPress doesn't appear to be installed AND the permissions
  # of it are root:root, let's chown it (likely a Docker-created directory)
  if [ "$uid" = '0' ] && [ "$(stat -c '%u:%g' .)" = '0:0' ]; then
    chown "$user:$group" .
  fi

  echo >&2 "WordPress not found in $PWD - copying now..."
  if [ -n "$(find -mindepth 1 -maxdepth 1 -not -name wp-content)" ]; then
    echo >&2 "WARNING: $PWD is not empty! (copying anyhow)"
  fi
  sourceTarArgs=(
    --create
    --file -
    --directory /usr/src/wordpress
    --owner "$user" --group "$group"
  )
  targetTarArgs=(
    --extract
    --file -
  )
  if [ "$uid" != '0' ]; then
    # avoid "tar: .: Cannot utime: Operation not permitted" and "tar: .: Cannot change mode to rwxr-xr-x: Operation not permitted"
    targetTarArgs+=(--no-overwrite-dir)
  fi
  # loop over "pluggable" content in the source, and if it already exists in the destination, skip it
  # https://github.com/docker-library/wordpress/issues/506 ("wp-content" persisted, "akismet" updated, WordPress container restarted/recreated, "akismet" downgraded)
  for contentPath in \
    /usr/src/wordpress/.htaccess \
    /usr/src/wordpress/wp-content/*/*/;
    do
      contentPath="${contentPath%/}"
      [ -e "$contentPath" ] || continue
      contentPath="${contentPath#/usr/src/wordpress/}" # "wp-content/plugins/akismet", etc.
      if [ -e "$PWD/$contentPath" ]; then
        echo >&2 "WARNING: '$PWD/$contentPath' exists! (not copying the WordPress version)"
        sourceTarArgs+=(--exclude "./$contentPath")
      fi
    done
  tar "${sourceTarArgs[@]}" . | tar "${targetTarArgs[@]}"
  echo >&2 "Complete! WordPress has been successfully copied to $PWD"
fi

wpEnvs=("${!WORDPRESS_@}")
if [ ! -s wp-config.php ] && [ "${#wpEnvs[@]}" -gt 0 ];
then
  for wpConfigDocker in \
    wp-config-docker.php \
    /usr/src/wordpress/wp-config-docker.php;
     do
        if [ -s "$wpConfigDocker" ];
        then
          echo >&2 "No 'wp-config.php' found in $PWD, but 'WORDPRESS_...' variables supplied; copying '$wpConfigDocker' (${wpEnvs[*]})"
          # using "awk" to replace all instances of "put your unique phrase here" with a properly unique string (for AUTH_KEY and friends to have safe defaults if they aren't specified with environment variables)
          awk '
              /put your unique phrase here/ {
                cmd = "head -c1m /dev/urandom | sha1sum | cut -d\\  -f1"
                cmd | getline str
                close(cmd)
                gsub("put your unique phrase here", str)
              }
              { print }
            ' "$wpConfigDocker" >wp-config.php
          if [ "$uid" = '0' ];
          then
            # attempt to ensure that wp-config.php is owned by the run user
            # could be on a filesystem that doesn't allow chown (like some NFS setups)
            chown "$user:$group" wp-config.php || true
          fi
          break
        fi
    done
fi
