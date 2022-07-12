#!/usr/bin/env bash

#
# =================================================================
# Test suite(s) of a single package
# =================================================================
#
# By default the root autoloader is used for faster dev feedback.
# Usage: bash bin/test/package.sh <path-to-package-directory> [suites...]
#
# To disable the root autoloader and install composer dependencies before each run use:
# Usage: ISOLATED=1 bash bin/test/package.sh <path-to-package-directory> [suites...]
#

# Exit immediately, No unset variables, Use last exit code in pipes.
set -euo pipefail

source ./bin/utils.sh # Include shell utils

# Expose codeception test environment
set -o allexport
source tests/.env.testing
set +o allexport

PACKAGE_DIR=$1
SUITES_TO_RUN="${*:2}" # All arguments expect first

if [ -z "$PACKAGE_DIR" ]; then
  error "Usage: bash bin/test/package.sh <path-to-package-directory> [suites...]" && exit 1
fi

if [ ! -d "$PACKAGE_DIR" ]; then
  error "PACKAGE_DIR $PACKAGE_DIR does not exist" && exit 1
fi

if [ -z "$SUITES_TO_RUN" ]; then
  # No explicit suite was passed, so we'll run all suites of the package.
  SUITES_TO_RUN=$(bash ./bin/test/get-package-codeception-suites.sh "$PACKAGE_DIR")
fi

if [ "$ISOLATED" == 1 ]; then
  heading "Running suite(s) $SUITES_TO_RUN from package $PACKAGE_DIR in isolation."

  cd "$PACKAGE_DIR" || (debug "Could not change to package directory $PACKAGE_DIR" && exit 1)

  composer update --ansi

  for SUITE in $SUITES_TO_RUN; do
    vendor/bin/codecept run "$SUITE"
  done

else

  heading "Running suite(s) $SUITES_TO_RUN from package $PACKAGE_DIR with the root autoloader."

  for SUITE in $SUITES_TO_RUN; do
    vendor/bin/codecept run "$PACKAGE_DIR::$SUITE"
  done

fi
