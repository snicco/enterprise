<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests;

use Snicco\Enterprise\Component\Condition\Context;
use WP_User;

trait CreateContext
{
    /**
     * @param array<string,string>          $_server
     * @param array<string,string|string[]> $_get
     * @param array<string,string|string[]> $_post
     * @param array<string,string|string[]> $_cookie
     */
    public function createContext(
        array $_server = [],
        array $_get = [],
        array $_post = [],
        array $_cookie = [],
        ?WP_User $user = null
    ): Context {
        return new Context($_server, $_get, $_post, $_cookie, $user);
    }
}
