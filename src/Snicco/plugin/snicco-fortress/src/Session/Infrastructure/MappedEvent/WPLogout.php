<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Session\Infrastructure\MappedEvent;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

use function wp_logout;

/**
 * @see wp_logout()
 */
final class WPLogout implements MappedHook
{
    use ClassAsName;
    use ClassAsPayload;

    /**
     * @psalm-readonly
     */
    public int $user_id;

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}
