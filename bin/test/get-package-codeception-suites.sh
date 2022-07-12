#!/usr/bin/env bash

#
# =================================================================
# Get all codeception suites of a package
# =================================================================
#
# This script takes a package directory as input an returns
# a list of all codeception suites that the package has.
#
# Usage: bash ./bin/test/get-package-codeception-suites.sh <path-to-package-directory>
# Example output: browser unit integration
#

# Exit immediately, No unset variables, Use last exit code in pipes.
set -euo pipefail

# Include shell utils
source ./bin/utils.sh

PACKAGE_DIR=$1

if [ -z "$PACKAGE_DIR" ]; then
    error "Usage: bash ./bin/test/get-package-codeception-suites.sh <path-to-package-directory>" && exit 1;
fi

if [ ! -d "$PACKAGE_DIR/tests" ]; then
     error "Directory $PACKAGE_DIR/tests does not exist" && exit 1;
fi

SUITE_FILES=$(find "$PACKAGE_DIR/tests" -type f -name "*.suite.yml" | xargs -n1 basename | sed 's/.suite.yml//g' )

echo "${SUITE_FILES[@]}"

