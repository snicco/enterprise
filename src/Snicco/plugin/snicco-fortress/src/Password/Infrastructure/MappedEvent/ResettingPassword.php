<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Password\Infrastructure\MappedEvent;

use LogicException;
use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use WP_Error;
use WP_User;

use function is_string;

final class ResettingPassword implements MappedHook
{
    use ClassAsPayload;
    use ClassAsName;

    private WP_Error $error;

    /**
     * @var WP_User|WP_Error
     */
    private $user;

    private ?string $password;

    /**
     * @param WP_User|WP_Error $user
     */
    public function __construct(WP_Error $error, $user)
    {
        $this->error = $error;
        $this->user = $user;
        $this->password = $this->parsePassword();
    }

    public function password(): string
    {
        // @codeCoverageIgnoreStart
        if (null === $this->password) {
            throw new LogicException('This event should not have been dispatched.');
        }

        // @codeCoverageIgnoreEnd

        return $this->password;
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

    public function addError(string $key, string $message): void
    {
        $this->error->add($key, $message);
    }

    public function shouldDispatch(): bool
    {
        return $this->user instanceof WP_User
               && null !== $this->password;
    }

    private function parsePassword(): ?string
    {
        if (isset($_POST['pass1']) && is_string($_POST['pass1'])) {
            return $_POST['pass1'];
        }

        if (isset($_POST['password_1']) && is_string($_POST['password_1'])) {
            return $_POST['password_1'];
        }

        if (! isset($_POST['password1'])) {
            return null;
        }

        if (! is_string($_POST['password1'])) {
            return null;
        }

        return $_POST['password1'];
    }
}
