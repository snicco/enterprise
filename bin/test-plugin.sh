#!/usr/bin/env bash

set -e
set -o pipefail
set -o allexport
source tests/.env.testing
set +o allexport

PLUGIN_DIR=$1

cd "$PLUGIN_DIR" || (echo 'Could not change directory.' && exit 1)

if [ ! -d "$PLUGIN_DIR/vendor" ]; then
  echo "Plugin has no vendor dir. Installing now."
  composer install
fi

composer test --working-dir="$PLUGIN_DIR"
