<?php

declare(strict_types=1);

// Include the WordPress stub files here. For some reason the performance is way better
// than including the stubs in the xml config.
// @see https://github.com/vimeo/psalm/issues/7570
require dirname(__DIR__).'/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';

const LOGGED_IN_COOKIE = 'logged_in_cookie';
const SECURE_AUTH_COOKIE = 'secure_auth_cookie';
const AUTH_COOKIE = 'auth_cookie';
const ADMIN_COOKIE_PATH = '/wp-admin';
const PLUGINS_COOKIE_PATH = '/wp-admin/plugins';
const COOKIEPATH = 'example.test';
const SITECOOKIEPATH = 'example.test';
const COOKIEHASH = 'secret_hash';

const SNICCO_AUTH_ENCRYPTION_SECRET = '';
const ABSPATH = '';
const WPINC = '';