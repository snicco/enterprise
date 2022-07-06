#!/usr/bin/env bash

set -e
set -o pipefail

PACKAGE_DIR=$1

if [ -z "$PACKAGE_DIR" ]; then
    echo "PACKAGE_DIR is empty" && exit 1;
fi

find "$PACKAGE_DIR/tests" -type f -name "*.suite.yml" | xargs basename | sed 's/.suite.yml//g'