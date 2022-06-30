#!/usr/bin/env bash

set -e
set -o pipefail

GREEN=$(tput setaf 2)
RED=$(tput setaf 1)
YELLOW=$(tput setaf 3)
NC=$(tput sgr0)

PLUGIN_DIR="$1"
OUTPUT_DIR="$2"

if [ ! -d "$PLUGIN_DIR" ]; then
  echo "$RED Directory $PLUGIN_DIR does not exit.$NC"
  exit 1
fi

if [ ! -d "$OUTPUT_DIR" ]; then
  mkdir -p "$OUTPUT_DIR"
  else
    rm -rf "$OUTPUT_DIR"
fi

echo "$GREEN Starting build for plugin $PLUGIN_DIR in $OUTPUT_DIR $NC"

cp -r "$PLUGIN_DIR" "$OUTPUT_DIR"

echo "$YELLOW Copied contents of $PLUGIN_DIR to $OUTPUT_DIR $NC"

rm -rf "$OUTPUT_DIR/vendor"

echo "$YELLOW Removed vendor directory.$NC"

composer install \
	--working-dir="$OUTPUT_DIR" \
	--no-dev \
 	--classmap-authoritative \
 	--no-scripts \
 	--no-plugins \
 	--no-interaction \
 	--quiet

echo "$YELLOW Installed composer dependencies. $NC"

#
#composer install --no-dev --prefer-dist --no-interaction
#php-scoper add-prefix --force -c ./php-scoper/scoper.inc.php --output-dir "$buildDir"
#composer dump-autoload --working-dir "$buildDir" --classmap-authoritative --no-dev
#rm "$buildDir"/composer.json
#
#php php-scoper/fix-static-file-autoloader.php ./"$buildDir"/vendor/composer
#
## Install dev dependencies in the root directory again
## so that we can run easy-coding-standards
#composer install
#
## Intentionally run multiple times.
#cp php-scoper/ecs-post-scoping.php "$buildDir"
#vendor/bin/ecs check --fix --config "$buildDir"/ecs-post-scoping.php
#vendor/bin/ecs check --fix --config "$buildDir"/ecs-post-scoping.php
#vendor/bin/ecs check --fix --config "$buildDir"/ecs-post-scoping.php
#rm "$buildDir"/ecs-post-scoping.php