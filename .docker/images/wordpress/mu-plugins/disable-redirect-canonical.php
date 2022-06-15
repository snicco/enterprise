<?php

declare(strict_types=1);

/*
 * Plugin name: Disable redirect canonical filter
 * Description: Completely removes the redirect_canonical callback for template redirect.
 */

/*
 * redirect_canonical is the biggest crap that has ever been written.
 * It's impossible to debug and will redirect https://nginx:8443 => https://nginx
 * causing all our tests to fail.
 */
remove_filter('template_redirect','redirect_canonical');
