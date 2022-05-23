<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Domain\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

final class SessionWasIdle implements Event
{
    use ClassAsPayload;
    use ClassAsName;

    /**
     * @psalm-readonly
     */
    public string $hashed_token;

    /**
     * @psalm-readonly
     */
    public int $user_id;

    public function __construct(string $hashed_token, int $user_id)
    {
        $this->hashed_token = $hashed_token;
        $this->user_id = $user_id;
    }
}
