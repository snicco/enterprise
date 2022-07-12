#!/usr/bin/env bash

#
# =================================================================
# Output all affected packages as newlines
# =================================================================
#
# Uses the GetAffectedPackages.php command to recursively
# determine affected packages based on their composer.json
# connection.
#
# Example output:
# src/Snicco/component/asset
# src/Snicco/plugin/snicco-fortress
#
#
# Exit immediately, No unset variables, Use last exit code in pipes.
set -euo pipefail

# Include shell utils
source ./bin/utils.sh

heading "Getting changed files since last commit..."

mapfile -t CHANGED_FILES < <(git diff --name-only)

debug "${CHANGED_FILES[@]}"

heading "Determining affected packages from changed files..."
AFFECTED_PACKAGES=$(php bin/snicco.php affected-packages "${CHANGED_FILES[@]}" --only-directories)

if [ -z "$AFFECTED_PACKAGES" ]; then
  debug "No packages affected."
else
  echo "$AFFECTED_PACKAGES"
fi
