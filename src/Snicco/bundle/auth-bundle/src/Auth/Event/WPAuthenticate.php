<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\Event;

use LogicException;
use Snicco\Component\BetterWPHooks\EventMapping\MappedFilter;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use WP_Error;
use WP_User;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\AuthBundle
 */
final class WPAuthenticate implements MappedFilter
{
    use ClassAsName;
    use ClassAsPayload;

    /**
     * @var WP_Error|WP_User|null
     */
    private $user;

    /**
     * @param WP_User|WP_Error|null $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    public function user(): WP_User
    {
        // @codeCoverageIgnoreStart
        if (! $this->user instanceof WP_User) {
            throw new LogicException('This event should not have been dispatched.');
        }

        // @codeCoverageIgnoreEnd
        return $this->user;
    }

    public function shouldDispatch(): bool
    {
        return $this->user instanceof WP_User;
    }

    public function filterableAttribute()
    {
        return $this->user;
    }
}
