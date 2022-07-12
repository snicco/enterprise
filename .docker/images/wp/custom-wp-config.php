<?php

/*
 * We use a modified version of the wp-config-docker.php file of the official docker image.
 * (@see https://github.com/docker-library/wordpress/blob/master/latest/php7.4/fpm-alpine/wp-config-docker.php)
 *
 * The official one is ok, but we need to be able to change DB_NAME based on the current scenario (dev,e2e tests).
 * Its not easily possible to achieve this with environment variables because we run e2e tests
 * from the "app" container but the env values need to be set in the "nginx" container.
 *
 * This file needs to stay in-sync with https://github.com/WordPress/WordPress/blob/master/wp-config-sample.php
 * (It gets parsed by the upstream wizard in https://github.com/WordPress/WordPress/blob/f27cb65e1ef25d11b535695a660e7282b98eb742/wp-admin/setup-config.php#L356-L392)
 *
 * Attention: Dont remove the comments in this file.
 */

// a helper function to lookup "env_FILE", "env", then fallback
if (!function_exists('getenv_docker')) {
    // https://github.com/docker-library/wordpress/issues/588 (WP-CLI will load this file 2x)
    function getenv_docker($env, $default) {
        if ($fileEnv = getenv($env . '_FILE')) {
            return rtrim(file_get_contents($fileEnv), "\r\n");
        }
        else if (($val = getenv($env)) !== false) {
            return $val;
        }
        else {
            return $default;
        }
    }
}

/*
 * WP Browser sets the WPBROWSER_HOST_REQUEST variable for wp-cli tests.
 * We set the "snicco-e2e-tester" username for all selenium tests.
 * We use valid credentials here as a default so that we can also run tests from PhpStorm (via SSH)
 * without needing to configure special environment variables every time. PhpStorm, unlike tests run via make, does
 * not know anything about settings the correct ENV variables for cli tests.
 */
if(getenv('WPBROWSER_HOST_REQUEST') || 'snicco-e2e-tester' === ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
    define( 'DB_NAME', getenv_docker('E2E_TEST_WORDPRESS_DB_NAME', 'snicco_enterprise_e2e_testing'));
    define( 'DB_USER', getenv_docker('WORDPRESS_DB_USER', 'root'));
    define( 'DB_PASSWORD', getenv_docker('WORDPRESS_DB_PASSWORD', 'root'));
    define( 'DB_HOST', getenv_docker('WORDPRESS_DB_HOST', 'db'));
} else {
    define( 'DB_NAME', getenv_docker('WORDPRESS_DB_NAME', 'wordpresss'));
    define( 'DB_USER', getenv_docker('WORDPRESS_DB_USER', 'example username') );
    define( 'DB_PASSWORD', getenv_docker('WORDPRESS_DB_PASSWORD', 'example password') );
    define( 'DB_HOST', getenv_docker('WORDPRESS_DB_HOST', 'mysql') );
}


/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', getenv_docker('WORDPRESS_DB_CHARSET', 'utf8') );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', getenv_docker('WORDPRESS_DB_COLLATE', '') );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         getenv_docker('WORDPRESS_AUTH_KEY',         'put your unique phrase here') );
define( 'SECURE_AUTH_KEY',  getenv_docker('WORDPRESS_SECURE_AUTH_KEY',  'put your unique phrase here') );
define( 'LOGGED_IN_KEY',    getenv_docker('WORDPRESS_LOGGED_IN_KEY',    'put your unique phrase here') );
define( 'NONCE_KEY',        getenv_docker('WORDPRESS_NONCE_KEY',        'put your unique phrase here') );
define( 'AUTH_SALT',        getenv_docker('WORDPRESS_AUTH_SALT',        'put your unique phrase here') );
define( 'SECURE_AUTH_SALT', getenv_docker('WORDPRESS_SECURE_AUTH_SALT', 'put your unique phrase here') );
define( 'LOGGED_IN_SALT',   getenv_docker('WORDPRESS_LOGGED_IN_SALT',   'put your unique phrase here') );
define( 'NONCE_SALT',       getenv_docker('WORDPRESS_NONCE_SALT',       'put your unique phrase here') );

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = getenv_docker('WORDPRESS_TABLE_PREFIX', 'wp_');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', (bool) getenv_docker('WORDPRESS_DEBUG', '') );

/* Add any custom values between this line and the "stop editing" line. */

// If we're behind a proxy server and using HTTPS, we need to alert WordPress of that fact
// see also https://wordpress.org/support/article/administration-over-ssl/#using-a-reverse-proxy
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) {
    $_SERVER['HTTPS'] = 'on';
}
// (we include this by default because reverse proxying is extremely common in container environments)

if ($configExtra = getenv_docker('WORDPRESS_CONFIG_EXTRA', '')) {
    eval($configExtra);
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';