<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

final class SessionWasRotated implements Event, ExposeToWP
{
    use ClassAsName;
    use ClassAsPayload;

    /**
     * @psalm-readonly
     */
    public int $user_id;

    /**
     * @psalm-readonly
     */
    public string $new_token_plain;

    /**
     * @psalm-readonly
     */
    public string $old_token_hashed;

    /**
     * @psalm-readonly
     */
    public int $expires_at;

    public function __construct(int $user_id, string $new_token_plain, string $old_token_hashed, int $expires_at)
    {
        $this->user_id = $user_id;
        $this->new_token_plain = $new_token_plain;
        $this->old_token_hashed = $old_token_hashed;
        $this->expires_at = $expires_at;
    }
}
