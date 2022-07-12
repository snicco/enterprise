set -e
set -o pipefail

buildDir="$1"

composer install --no-dev --prefer-dist --no-interaction
php-scoper add-prefix --force -c ./php-scoper/scoper.inc.php --output-dir "$buildDir"
composer dump-autoload --working-dir "$buildDir" --classmap-authoritative --no-dev
rm "$buildDir"/composer.json

php php-scoper/fix-static-file-autoloader.php ./"$buildDir"/vendor/composer

# Install dev dependencies in the root directory again
# so that we can run easy-coding-standards
composer install

# Intentionally run multiple times.
cp php-scoper/ecs-post-scoping.php "$buildDir"
vendor/bin/ecs check --fix --config "$buildDir"/ecs-post-scoping.php
vendor/bin/ecs check --fix --config "$buildDir"/ecs-post-scoping.php
vendor/bin/ecs check --fix --config "$buildDir"/ecs-post-scoping.php
rm "$buildDir"/ecs-post-scoping.php