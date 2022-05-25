<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Enterprise\AuthBundle\Auth\Event\WPAuthenticate;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\AuthBundle\Auth\Event\WPAuthenticate2FaChallengeRedirect;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\TwoFactorAuthenticator;

use function is_string;
use function wp_safe_redirect;

final class AuthenticationEventHandler implements EventSubscriber
{
    
    private TwoFactorSettings $two_factor_settings;
    
    private TwoFactorChallengeService $challenge_service;
    
    private EventDispatcher $event_dispatcher;
    
    private UrlGenerator $url_generator;
    
    public function __construct(
        TwoFactorSettings $two_factor_settings,
        TwoFactorChallengeService $challenge_service,
        EventDispatcher $event_dispatcher,
        UrlGenerator $url_generator
    ) {
        $this->two_factor_settings = $two_factor_settings;
        $this->event_dispatcher = $event_dispatcher;
        $this->url_generator = $url_generator;
        $this->challenge_service = $challenge_service;
    }
    
    public static function subscribedEvents() :array
    {
        return [
            WPAuthenticate::class => 'onWPAuthenticate',
        ];
    }
    
    /**
     * @todo Browser test
     */
    public function onWPAuthenticate(WPAuthenticate $event) :void
    {
        $user = $event->user();
        
        if ( ! $this->two_factor_settings->isSetupCompleteForUser($user->ID)) {
            return;
        }
        
        $challenge_id = $this->challenge_service->createChallenge($user->ID);
        
        $args = [
            TwoFactorAuthenticator::CHALLENGE_ID => $challenge_id,
        ];
        
        if (isset($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])) {
            $args['redirect_to'] = $_REQUEST['redirect_to'];
        } elseif (isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])) {
            $args['redirect_to'] = $_SERVER['HTTP_REFERER'];
        }
        
        if(isset($_REQUEST['remember_me']) || isset($_REQUEST['rememberme']) || isset($_REQUEST['remember'])) {
            $args['remember_me'] = 1;
        }
        
        $redirect_url = $this->url_generator->toRoute('snicco_auth.2fa.challenge', $args);
        
        $event = $this->event_dispatcher->dispatch(
            new WPAuthenticate2FaChallengeRedirect(
                $user,
                $redirect_url
            )
        );
        
        if ( ! $event->do_shutdown) {
            return;
        }
        
        // @codeCoverageIgnoreStart
        wp_safe_redirect($redirect_url);
        
        exit(0);
        // @codeCoverageIgnoreEnd
    }
    
}
