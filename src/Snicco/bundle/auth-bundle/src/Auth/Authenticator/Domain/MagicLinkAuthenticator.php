<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\SignedUrl\Exception\SignedUrlException;
use Snicco\Component\SignedUrl\SignedUrlValidator;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\LoginResult;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\Authenticator;
use Snicco\Enterprise\AuthBundle\Auth\Event\FailedMagicLinkAuthentication;
use Snicco\Enterprise\AuthBundle\Auth\User\Domain\UserProvider;

final class MagicLinkAuthenticator extends Authenticator
{
    
    private EventDispatcher $event_dispatcher;
    
    private SignedUrlValidator $signed_url_validator;
    
    private UserProvider $user_provider;
    
    public function __construct(
        EventDispatcher $event_dispatcher,
        SignedUrlValidator $signed_url_validator,
        UserProvider $user_provider
    ) {
        $this->event_dispatcher = $event_dispatcher;
        $this->signed_url_validator = $signed_url_validator;
        $this->user_provider = $user_provider;
    }
    
    public function attempt(Request $request, callable $next) :LoginResult
    {
        $id = (string)$request->query('user_id');
        
        try {
            $this->signed_url_validator->validate($request->getRequestTarget());
        } catch (SignedUrlException $e) {
            $this->event_dispatcher->dispatch(new FailedMagicLinkAuthentication((string)$request->ip(), $id));
            
            return LoginResult::failed();
        }
        
        $user = $this->user_provider->getUserByIdentifier($id);
        
        $remember = null;
        
        if ($request->has('remember_me')) {
            $remember = $request->boolean('remember_me');
        }
        
        return new LoginResult($user, $remember);
    }
    
}
