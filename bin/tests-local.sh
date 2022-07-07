#!/usr/bin/env bash

set -e
set -o pipefail

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

START_TOTAL=$(date +%s)
FAILED=()

#
# =================================================================
# Running top level suites in the Monorepo
# =================================================================
#
# These are all the suites inside the ./tests folder.
#
ROOT_SUITES=$(bash ./bin/package-suites.sh .)
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
  PACKAGE_SUITES+=($(bash ./bin/package-suites.sh "$DIR"))
done
UNIQUE_SUITES=($(echo "${PACKAGE_SUITES[@]}" | tr ' ' '\n' | sort -u -r | tr '\n' ' '))

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
    if [ -z "$XDEBUG_MODE" ]; then
      XDEBUG_MODE=coverage
    fi
    export XDEBUG_MODE="$XDEBUG_MODE"
  fi

  vendor/bin/codecept run "*::$UNIQUE_SUITE" "${ARGS[@]}" || FAILED+=("$UNIQUE_SUITE")

  SUITE_END["$UNIQUE_SUITE"]="$(date +%s)"
done

heading "Results"

END_TOTAL=$(date +%s)

printf "%-20s $YELLOW%ss$NC\n" "Root suites:" "$((END_ROOT_SUITES - START_ROOT_SUITES))"

for SUITE in "${!SUITE_START[@]}"; do
  printf "%-20s $YELLOW%ss$NC\n" "$SUITE:" "$((${SUITE_END[$SUITE]} - ${SUITE_START[$SUITE]}))"
done
echo "---------------------------"
printf "%-20s $YELLOW%ss$NC\n\n" "Total:" "$((END_TOTAL - START_TOTAL))"

if [ ${#FAILED[@]} -eq 0 ]; then
  echo -e "${GREEN}[SUCCESS] All test suites passed.$NC"
else
  echo -e "${RED}[ERROR] The following test suites failed:$NC"
  for FAILED_SUITE in "${FAILED[@]}"; do
    echo "$FAILED_SUITE"
  done
  echo ""
  exit 1
fi
