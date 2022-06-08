set -Eeuo pipefail

# Run the official image entrypoint without starting php-fpm
wordpress.official-entrypoint.modified.sh

# Install WordPress so that we have a fully functioning
# install in CI/CD
if ! wp --allow-root core is-installed --path="/var/www/html"; then
  echo "WordPress is not installed. Installing now."
  wp --allow-root core install --path="/var/www/html" --url="snicco-enterprise.test" --title="Snicco Enterprise" --admin_user=admin --admin_password=admin --admin_email=admin@snicco.test
  wp --allow-root config set WP_DEBUG true --raw
  wp --allow-root config set SCRIPT_DEBUG true --raw
  wp --allow-root rewrite structure '/%postname%/' --hard
else
  echo "WordPress is already installed"
fi

# Start the php-fpm process that is normally started in the official
# image entrypoint
exec php-fpm "$@"
