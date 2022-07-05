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

heading "Getting changed files since last commit..."
CHANGED_FILES=$(git diff --name-only)
echo "$CHANGED_FILES"

heading "Determining affected packages..."
# shellcheck disable=SC2086
AFFECTED_PACKAGES=$(php bin/snicco.php affected-packages $CHANGED_FILES --only-directories)
echo "$AFFECTED_PACKAGES"

FAILED=()

for PACKAGE in $AFFECTED_PACKAGES; do
  heading "Testing package $PACKAGE"
  bash bin/test-plugin.sh "$PACKAGE" || FAILED+=("$PACKAGE")
done

echo ""

if [ ${#FAILED[@]} -eq 0 ]; then
  echo -e "${GREEN}[OK] All tests passing]"
else
  echo -e "${RED}[ERROR] The following package(s) did not pass the tests:$NC"
  for FAIL in "${FAILED[@]}"; do
    echo "$FAIL"
  done
fi
