#!/usr/bin/env bash

set -e
set -o pipefail

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m'

PLUGIN_SRC_DIR="$1"
OUTPUT_DIR="$2"
BUILD_VERSION="$3"

if [ ! -d "$PLUGIN_SRC_DIR" ]; then
  echo -e "$RED Directory $PLUGIN_SRC_DIR does not exit.$NC"
  exit 1
fi

if [ ! -f "$PLUGIN_SRC_DIR/php-scoper.php" ]; then
  echo -e "$RED Directory $PLUGIN_SRC_DIR does not have a php-scoper.php configuration file. $NC"
  exit 1
fi

if [ -z "$BUILD_VERSION" ]; then
  echo -e "$RED No build prefix specified. Usage: build_plugin.sh <plugin-dir> <output-dir> <build-version>$NC"
  exit 1
else
  export BUILD_VERSION
fi

if [ ! -d "$OUTPUT_DIR" ]; then
  mkdir -p "$OUTPUT_DIR"
else
  rm -rf "$OUTPUT_DIR"
fi

echo -e "$GREEN Starting production build for plugin $PLUGIN_SRC_DIR in $OUTPUT_DIR with build-version $BUILD_VERSION .$NC"

rm -rf "$PLUGIN_SRC_DIR/vendor"

echo -e "$YELLOW Removed vendor directory.$NC"

# @see https://github.com/composer/composer/issues/9368#issuecomment-718112361
# @see https://github.com/composer/composer/issues/9368#issuecomment-718198161
export COMPOSER_ROOT_VERSION="dev-master"

composer install \
  --working-dir="$PLUGIN_SRC_DIR" \
  --no-dev \
  --classmap-authoritative \
  --no-scripts \
  --no-plugins \
  --prefer-dist \
  --no-interaction \

echo -e "$YELLOW Installed composer production dependencies. $NC"

php-scoper add-prefix -c "$PLUGIN_SRC_DIR/php-scoper.php" \
  --force \
  --output-dir "$OUTPUT_DIR" \
  --no-interaction \

mkdir -p "$OUTPUT_DIR"/var/cache
mkdir -p "$OUTPUT_DIR"/var/log

echo -e "$YELLOW Scoped plugin $PLUGIN_SRC_DIR in $OUTPUT_DIR $NC"

# We need the composer.json file to dump the autoloader.
cp "$PLUGIN_SRC_DIR/composer.json" "$OUTPUT_DIR/composer.json"

composer dump-autoload \
  --working-dir="$OUTPUT_DIR" \
  --no-dev \
  --classmap-authoritative \
  --no-scripts \
  --no-plugins \
  --no-interaction \

rm "$OUTPUT_DIR/composer.json"

echo -e "$YELLOW Dumped composer autoloader.$NC"

php .docker/images/app/bin/fix-static-file-autoloader.php "$OUTPUT_DIR/vendor/composer"

echo -e "$YELLOW Fixed static file autoloader.$NC"

echo -e "$YELLOW Fixed code style of scoped code.$NC"

echo -e "$GREEN Finished build for plugin in $OUTPUT_DIR.$NC"