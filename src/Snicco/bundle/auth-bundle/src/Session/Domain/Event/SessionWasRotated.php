<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Domain\Event;

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

    public function __construct(int $user_id, string $new_token_plain, string $old_token_hashed)
    {
        $this->user_id = $user_id;
        $this->new_token_plain = $new_token_plain;
        $this->old_token_hashed = $old_token_hashed;
    }
}
