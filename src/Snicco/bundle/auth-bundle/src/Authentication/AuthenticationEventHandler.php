<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\WPAuthenticate;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\WPSignonLogin2FaChallengeRedirect;

use function wp_safe_redirect;

final class AuthenticationEventHandler implements EventSubscriber
{
    
    private TwoFactorSettings $two_factor_settings;
    private EventDispatcher $event_dispatcher;
    private UrlGenerator $url_generator;
    
    public function __construct(
        TwoFactorSettings $two_factor_settings,
        EventDispatcher $event_dispatcher,
        UrlGenerator $url_generator
    ) {
        $this->two_factor_settings = $two_factor_settings;
        $this->event_dispatcher = $event_dispatcher;
        $this->url_generator = $url_generator;
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
        
        $event = $this->event_dispatcher->dispatch(new WPSignonLogin2FaChallengeRedirect(
            $user,
        ));
        
        if(false === $event->do_redirect) {
            return;
        }
        // @codeCoverageIgnoreStart
    
        $redirect_url = $this->url_generator->toRoute('');
        
        wp_safe_redirect('/foo');
        
        exit(0);
        // @codeCoverageIgnoreEnd
    }
    
}
