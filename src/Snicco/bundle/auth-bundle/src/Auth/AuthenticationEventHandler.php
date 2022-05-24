<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Enterprise\AuthBundle\Auth\Event\WPAuthenticate;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\AuthBundle\Auth\Event\WPAuthenticate2FaChallengeRedirect;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure\TwoFactorChallengeGenerator;

use function wp_safe_redirect;

final class AuthenticationEventHandler implements EventSubscriber
{
    private TwoFactorSettings $two_factor_settings;
    private TwoFactorChallengeGenerator $generate_challenge;
    private EventDispatcher $event_dispatcher;
    private UrlGenerator $url_generator;
    
    public function __construct(
        TwoFactorSettings $two_factor_settings,
        TwoFactorChallengeGenerator $challenges,
        EventDispatcher $event_dispatcher,
        UrlGenerator $url_generator
    ) {
        $this->two_factor_settings = $two_factor_settings;
        $this->event_dispatcher = $event_dispatcher;
        $this->url_generator = $url_generator;
        $this->generate_challenge = $challenges;
    }
    
    public static function subscribedEvents() :array
    {
        return [
            WPAuthenticate::class => 'onWPAuthenticate',
        ];
    }
    
    public function onWPAuthenticate(WPAuthenticate $event) :void
    {
        $user = $event->user();
        
        if ( ! $this->two_factor_settings->isSetupCompleteForUser($user->ID)) {
            return;
        }
        
        $token = $this->generate_challenge->urlToken($user->ID);
        
        $redirect_url = $this->url_generator->toRoute(
            'snicco_auth.2fa.challenge', ['token' => $token]
        );
        
        $event = $this->event_dispatcher->dispatch(new WPAuthenticate2FaChallengeRedirect(
            $user,
            $redirect_url
        ));
        
        if(false === $event->do_shutdown) {
            return;
        }
        // @codeCoverageIgnoreStart
        wp_safe_redirect($redirect_url);
        
        exit(0);
        // @codeCoverageIgnoreEnd
    }
    
}
