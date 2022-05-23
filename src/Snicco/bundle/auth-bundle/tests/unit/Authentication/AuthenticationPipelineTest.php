<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\unit\Authentication;

use WP_User;
use Codeception\Test\Unit;
use Nyholm\Psr7\ServerRequest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Enterprise\Bundle\Auth\Authentication\Authenticator\LoginResult;
use Snicco\Enterprise\Bundle\Auth\Authentication\Authenticator\Authenticator;
use Snicco\Enterprise\Bundle\Auth\Authentication\Authenticator\AuthenticationPipeline;

final class AuthenticationPipelineTest extends Unit
{
    
    /**
     * @test
     */
    public function that_authenticators_are_called_in_the_correct_order() :void
    {
        $pipeline = new AuthenticationPipeline([
            new Auth1(),
            new Auth2()
        ]);
        
        $request = Request::fromPsr(new ServerRequest('POST', '/login'));
        $res = $pipeline->attempt($request);
        $this->assertFalse($res->isSuccess());
        $this->assertNull($res->response());
    
        $pipeline = new AuthenticationPipeline([
            new Auth1(),
            new Auth2()
        ]);
    
        $res = $pipeline->attempt($request->withParsedBody(['custom_response' => true]));
    
        $this->assertFalse($res->isSuccess());
        $this->assertNotNull($res->response());
    }
    
}

class Auth1 extends Authenticator
{
    public function attempt(Request $request, callable $next) :LoginResult
    {
        return $next($request);
    }
}

class Auth2 extends Authenticator {
    
    public function attempt(Request $request, callable $next) :LoginResult
    {
        if(true !== $request->post('custom_response')) {
            return LoginResult::failed();
        }
        
        return LoginResult::failed(new Response(new \Nyholm\Psr7\Response(401)));
    }
    
}