#!/usr/bin/env bash

set -e
set -o pipefail

PACKAGE_DIR=$1

if [ -z "$PACKAGE_DIR" ]; then
    echo "PACKAGE_DIR is empty" && exit 1;
fi

if [ ! -d "$PACKAGE_DIR/tests" ]; then
     echo "Directory $PACKAGE_DIR/tests does not exist" && exit 1;
fi

SUITE_FILES=$(find "$PACKAGE_DIR/tests" -type f -name "*.suite.yml")

for FILE in $SUITE_FILES ; do
    basename "$FILE" | sed 's/.suite.yml//g'
done