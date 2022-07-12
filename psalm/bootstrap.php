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

define("SECURE_AUTH_SALT", bin2hex(random_bytes(32)));

$false_or_string = (random_int(1,3) > 2) ? 'string' : false;
define('COOKIE_DOMAIN',  $false_or_string);

const ABSPATH = '';
const WPINC = '';