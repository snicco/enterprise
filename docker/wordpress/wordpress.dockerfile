ARG PHP_VERSION
ARG WP_VERSION

FROM wordpress:${WP_VERSION}-php${PHP_VERSION}-fpm-alpine

# This is a quick and dirty way to get a wp-cli installation into this container.
# It is only used during the startup phase.
# To run actual command during development the "wp" container should be used.
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

# Add a simple mu-plugin to force sending emails with the mailhog container.
COPY ./mu-plugins/mailhog-smtp.php /var/www/html/wp-content/mu-plugins/mailhog-smtp.php

COPY wordpress.entrypoint.sh /usr/local/bin
RUN chmod +x /usr/local/bin/wordpress.entrypoint.sh

COPY wordpress.official-entrypoint.modified.sh /usr/local/bin
RUN chmod +x /usr/local/bin/wordpress.official-entrypoint.modified.sh

ENTRYPOINT ["/bin/sh","-c"]
CMD ["/usr/local/bin/wordpress.entrypoint.sh"]

