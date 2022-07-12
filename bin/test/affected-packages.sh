#!/usr/bin/env bash

#
# =================================================================
# Test all affected packages since the last commit
# =================================================================
#
# By default, this script will test packages using the root
# autoloader of the monorepo. If you pass ISOLATED=1
# each packages composer dependencies will be installed before
# running the tests. (Takes longer, but reflects real world usage better).
#
# Usage: bash ./bin/test/test-affected-sequential.sh
# Usage: ISOLATED=1 bash ./bin/test/test-affected-packages.sh
# Usage: FAST=1 bash ./bin/test/test-affected-packages.sh (Only run usecase and unit suites)
#

# Exit immediately, No unset variables, Use last exit code in pipes.
set -euo pipefail

# Include shell utils
source ./bin/utils.sh

# Expose codeception test environment
set -o allexport
source tests/.env.testing
set +o allexport


AFFECTED_PACKAGES=$(bash ./bin/test/get-affected-packages.sh)
FAILED=()

debug "$AFFECTED_PACKAGES"

START_TOTAL=$(date +%s)
declare -A PACKAGE_START
declare -A PACKAGE_END

for PACKAGE in $AFFECTED_PACKAGES; do

  SUITES=()
  if [ "$FAST_ONLY" == 1 ]; then
    mapfile -t PACKAGE_SUITES < <(bash ./bin/test/get-package-codeception-suites.sh "$PACKAGE") 2> /dev/null

    if inArray unit "${PACKAGE_SUITES[@]}"; then
      SUITES+=(unit)
    fi

    if inArray usecase "${PACKAGE_SUITES[@]}"; then
      SUITES+=(usecase)
    fi

  fi

  PACKAGE_START["$PACKAGE"]="$(date +%s)"
  bash ./bin/test/package.sh "$PACKAGE" "${SUITES[@]}" || FAILED+=("$PACKAGE")
  PACKAGE_END["$PACKAGE"]="$(date +%s)"

done

heading "Runtime results"

END_TOTAL=$(date +%s)

for PACKAGE in "${!PACKAGE_START[@]}"; do
  printf "%-50s $YELLOW%ss$NC\n" "$PACKAGE:" "$((${PACKAGE_END[$PACKAGE]} - ${PACKAGE_START[$PACKAGE]}))" >&2
done
debug "------------------------------------------------------"
printf "%-50s $YELLOW%ss$NC\n\n" "Total:" "$((END_TOTAL - START_TOTAL))" >&2

if [ ${#FAILED[@]} -eq 0 ]; then
  success "All tests passing"
else
  error "The following package(s) did not pass the tests:"
  for FAIL in "${FAILED[@]}"; do
    debug "$FAIL"
  done
  exit 1
fi
