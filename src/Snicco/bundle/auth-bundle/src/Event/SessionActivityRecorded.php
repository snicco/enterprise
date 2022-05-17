<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Event;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use WP_User;

use function time;

final class SessionActivityRecorded implements MappedHook
{
    use ClassAsPayload;
    use ClassAsName;

    /**
     * @psalm-readonly
     */
    public string $raw_token;

    /**
     * @psalm-readonly
     */
    public int  $user_id;

    /**
     * @psalm-readonly
     */
    public int $timestamp;

    public function __construct(WP_User $user, string $raw_token)
    {
        $this->raw_token = $raw_token;
        $this->user_id = $user->ID;
        $this->timestamp = time();
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}
