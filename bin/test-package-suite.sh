#!/usr/bin/env bash

set -e
set -o pipefail
set -o allexport
source tests/.env.testing
set +o allexport

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

function heading() {
  echo ""
  echo -e "$YELLOW $1"
  echo -e "=================================================================$NC"
  echo ""
}

PACKAGE=$1
SUITE=$2

if [ -z "$PACKAGE" ]; then
    echo "PACKAGE is empty" && exit 1;
fi

if [ -z "$SUITE" ]; then
    echo "SUITE is empty" && exit 1;
fi

cd "$PACKAGE" || (echo 'Could not change directory.' && exit 1)

heading "Running test suite '$SUITE' from $PACKAGE"

if [ ! -d "$PACKAGE/vendor" ]; then
  echo "Package $PACKAGE has no vendor directory. Installing now..."
  composer install --ansi
fi

vendor/bin/codecept run "$SUITE"

rm -rf "$PACKAGE/vendor"