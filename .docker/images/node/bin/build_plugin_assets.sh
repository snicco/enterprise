#!/usr/bin/env bash

set -e
set -o pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

PLUGIN_SRC_DIR="$1"
OUTPUT_DIR="$2"

if [ ! -d "$PLUGIN_SRC_DIR" ]; then
  echo -e "$RED Directory $PLUGIN_SRC_DIR does not exit.$NC"
  exit 1
fi

if [ ! -d "$OUTPUT_DIR" ]; then
  echo -e "$RED Directory $OUTPUT_DIR does not exit.$NC"
  exit 1
fi

if [ -d "$OUTPUT_DIR/public" ]; then
  rm -rf "$OUTPUT_DIR/public/*"
else
  mkdir "$OUTPUT_DIR/public"
fi

rm -rf "$PLUGIN_SRC_DIR/public"

yarn --cwd "$PLUGIN_SRC_DIR" run build:prod

cp -R "$PLUGIN_SRC_DIR/public/"* "$OUTPUT_DIR/public"