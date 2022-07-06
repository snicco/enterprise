#!/usr/bin/env bash

set -e
set -o pipefail
set -o allexport
source tests/.env.testing
set +o allexport

PACKAGE_DIR=$1

if [ -z "$PACKAGE_DIR" ]; then
    echo "PACKAGE_DIR is empty" && exit 1;
fi

cd "$PACKAGE_DIR" || (echo 'Could not change directory.' && exit 1)

if [ ! -d "$PACKAGE_DIR/vendor" ]; then
  echo "Package $PACKAGE_DIR has no vendor directory. Installing now..."
  composer install --ansi
fi

composer test
