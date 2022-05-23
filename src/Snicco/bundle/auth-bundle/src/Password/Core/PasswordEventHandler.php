<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Password\Core;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Enterprise\Bundle\Auth\Password\Core\PasswordPolicy;
use Snicco\Enterprise\Bundle\Auth\Password\Core\Event\ResettingPassword;
use Snicco\Enterprise\Bundle\Auth\Password\Core\Event\UpdatingUserInAdminArea;
use Snicco\Enterprise\Bundle\Auth\Password\Core\Exception\InsufficientPasswordEntropy;
use Snicco\Enterprise\Bundle\Auth\Password\Core\Exception\InsufficientPasswordLength;
use Snicco\Enterprise\Bundle\Auth\Password\Core\Exception\PasswordLengthExceeded;
use WP_User;

use function __;
use function array_intersect;
use function get_bloginfo;
use function is_string;

final class PasswordEventHandler implements EventSubscriber
{
    /**
     * @var string[]
     */
    private array $excluded_roles;

    /**
     * @param string[] $excluded_roles
     */
    public function __construct(array $excluded_roles = [])
    {
        $this->excluded_roles = $excluded_roles;
    }

    public static function subscribedEvents(): array
    {
        return [
            UpdatingUserInAdminArea::class => 'onAdminUserUpdate',
            ResettingPassword::class => 'onPasswordReset',
        ];
    }

    public function onAdminUserUpdate(UpdatingUserInAdminArea $event): void
    {
        $user = new WP_User($event->userId());

        if (array_intersect($this->excluded_roles, $user->roles)) {
            return;
        }

        /** @var string $new_password */
        $new_password = $_POST['pass1'];

        /** @var string[] $context */
        $context = [get_bloginfo()];

        if (isset($_POST['first_name']) && is_string($_POST['first_name'])) {
            $context[] = $_POST['first_name'];
        }

        if (isset($_POST['last_name']) && is_string($_POST['last_name'])) {
            $context[] = $_POST['last_name'];
        }

        if (isset($_POST['nickname']) && is_string($_POST['nickname'])) {
            $context[] = $_POST['nickname'];
        }

        if (isset($_POST['display_name']) && is_string($_POST['display_name'])) {
            $context[] = $_POST['display_name'];
        }

        if (isset($_POST['email']) && is_string($_POST['email'])) {
            $context[] = $_POST['email'];
        }

        $policy = new PasswordPolicy();

        try {
            $policy->check($new_password, $context);
        } catch (InsufficientPasswordLength $e) {
            $event->addError(
                'insufficient_pw_length',
                __('Passwords must have at least 12 characters.', 'snicco-auth')
            );
        } catch (PasswordLengthExceeded $e) {
            $event->addError(
                'pw_length_exceeded',
                __('Passwords must not have more than 4096 characters.', 'snicco-auth')
            );
        } catch (InsufficientPasswordEntropy $e) {
            $event->addError(
                'insufficient_pw_entropy',
                __('Your password is to insecure or contains references to your personal information. Please generate a secure password.', 'snicco-auth')
            );
        }
    }

    public function onPasswordReset(ResettingPassword $event): void
    {
        $user = $event->user();

        if (array_intersect($this->excluded_roles, $user->roles)) {
            return;
        }

        /** @var string[] $context */
        $context = [
            get_bloginfo(),
            $user->user_email,
            $user->first_name,
            $user->last_name,
            $user->display_name,
            $user->nickname,
            $user->user_nicename,
            $user->user_login,
        ];

        $policy = new PasswordPolicy();

        try {
            $policy->check($event->password(), $context);
        } catch (InsufficientPasswordLength $e) {
            $event->addError(
                'insufficient_pw_length',
                __('Passwords must have at least 12 characters.', 'snicco-auth')
            );
        } catch (PasswordLengthExceeded $e) {
            $event->addError(
                'pw_length_exceeded',
                __('Passwords must not have more than 4096 characters.', 'snicco-auth')
            );
        } catch (InsufficientPasswordEntropy $e) {
            $event->addError(
                'insufficient_pw_entropy',
                __('Your password is to insecure or contains references to your personal information. Please generate a secure password.', 'snicco-auth')
            );
        }
    }
}
