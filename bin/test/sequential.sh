#!/usr/bin/env bash

#
# =================================================================
# Test all packages sequentially in the same docker container
# =================================================================
#
# This script will parse all unique suites of all packages
# and then run all suites with the same name together.
# i.e. All unit tests of all packages are run together, than all integration tests of all packages.
#
# This script has two purposes:
#
# 1) To provide fast feedback during development
# 2) To collect code coverage
#
# We do not use this script in CI because it uses the root autoloader
# that is aggregated out of all packages. Its thus much faster
# then installing dependencies of each package, but it also has
# the drawback that packages might use dependencies not explicitly declared
# in their respective composer.json/package.json files.
#
#
# Usage: bash ./bin/test/test-sequential.sh
# Usage: COVERAGE=1 bash ./bin/test/test-sequential.sh
#

# Exit immediately, No unset variables, Use last exit code in pipes.
set -euo pipefail

# Include shell utils
source ./bin/utils.sh

# Expose codeception test environment
set -o allexport
source tests/.env.testing
set +o allexport

COVERAGE=${COVERAGE:=0}
XDEBUG_MODE=${XDEBUG_MODE:=coverage}

START_TOTAL=$(date +%s)
FAILED=()

#
# =================================================================
# Running top level suites in the Monorepo
# =================================================================
#
# These are all the suites inside the ./tests folder.
#
ROOT_SUITES=$(bash ./bin/test/get-package-codeception-suites.sh .)
heading "Running all top-level suites [$ROOT_SUITES]"

START_ROOT_SUITES=$(date +%s)
for ROOT_SUITE in $ROOT_SUITES; do
  # todo We do not collect coverage for the root packages currently.
  vendor/bin/codecept run "$ROOT_SUITE" || FAILED+=("Root")
done
END_ROOT_SUITES=$(date +%s)

#
# =================================================================
# Finding all unique package suites
# =================================================================
#
# We find all unique codeception suites from all packages.
# We then run them grouped with the new functionality we merged
# into codeception.
#
# IE: vendor/bin/codecept run *::unit => Runs all unit suites
# in all included packages.
#
PACKAGE_DIRS=$(find src/Snicco/*/* -type d -maxdepth 0)
PACKAGE_SUITES=()
for DIR in $PACKAGE_DIRS; do
  mapfile -t TMP < <(bash ./bin/test/get-package-codeception-suites.sh "$DIR")
  PACKAGE_SUITES+=("${TMP[@]}")
done
mapfile -t UNIQUE_SUITES < <(printf '%s\n' "${PACKAGE_SUITES[@]}" | sort -u -r ) # We are also sorting reversely, so that unit and usecase run first.

#
# =================================================================
# Testing package suites
# =================================================================
#
# We cant run all suites in one command since its not
# supported by WPBrowser due to WordPress global side effects.
#
declare -A SUITE_START
declare -A SUITE_END

for UNIQUE_SUITE in "${UNIQUE_SUITES[@]}"; do
  SUITE_START["$UNIQUE_SUITE"]="$(date +%s)"

  heading "Running all $UNIQUE_SUITE suites at once"

  ARGS=()
  if [ "$COVERAGE" == 1 ]; then
    ARGS+=("--coverage" "--coverage-xml" "$UNIQUE_SUITE-coverage.xml")
    export XDEBUG_MODE="$XDEBUG_MODE"
  fi

  # @see https://codeception.com/docs/08-Customization#One-Runner-for-Multiple-Applications
  vendor/bin/codecept run "*::$UNIQUE_SUITE" "${ARGS[@]}" || FAILED+=("$UNIQUE_SUITE")

  SUITE_END["$UNIQUE_SUITE"]="$(date +%s)"
done

heading "Results"

END_TOTAL=$(date +%s)

printf "%-20s $YELLOW%ss$NC\n" "Root suites:" "$((END_ROOT_SUITES - START_ROOT_SUITES))" >&2

for SUITE in "${!SUITE_START[@]}"; do
  printf "%-20s $YELLOW%ss$NC\n" "$SUITE:" "$((${SUITE_END[$SUITE]} - ${SUITE_START[$SUITE]}))"
done
debug "---------------------------"
printf "%-20s $YELLOW%ss$NC\n\n" "Total:" "$((END_TOTAL - START_TOTAL))" >&2

if [ ${#FAILED[@]} -eq 0 ]; then

  success "All test suites passed."

else

  error "The following test suites failed:"

  for FAILED_SUITE in "${FAILED[@]}"; do
    debug "$FAILED_SUITE"
  done

  exit 1
fi
