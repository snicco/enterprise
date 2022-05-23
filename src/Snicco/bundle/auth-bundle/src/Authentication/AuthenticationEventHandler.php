<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\WPAuthenticate;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\TwoFactorSettings;

use function wp_safe_redirect;

final class AuthenticationEventHandler implements EventSubscriber
{
    private TwoFactorSettings $two_factor_settings;

    private UrlGenerator      $url_generator;

    public function __construct(
        TwoFactorSettings $two_factor_settings,
        UrlGenerator $url_generator
    ) {
        $this->two_factor_settings = $two_factor_settings;
        $this->url_generator = $url_generator;
    }

    public static function subscribedEvents(): array
    {
        return [
            WPAuthenticate::class => 'onWPAuthenticate',
        ];
    }

    public function onWPAuthenticate(WPAuthenticate $event): void
    {
        $user = $event->user();

        if (! $this->two_factor_settings->isSetupCompleteForUser($user->ID)) {
            return;
        }

        $url = $this->url_generator->toRoute('snicco.2fa.challenge', [
            'user_id' => $user->ID,
        ]);

        wp_safe_redirect($url);

        exit();
    }
}
