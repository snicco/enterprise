<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\integration\Authentication\Http;

use Snicco\Enterprise\Bundle\Auth\Tests\AuthWebTestCase;

final class LoginRedirectTest extends AuthWebTestCase
{
    
    /**
     * @test
     */
    public function test_foo() :void
    {
        $browser = $this->getBrowser();
        
        $browser->followRedirects(false);
        $browser->request('GET', '/foo');
        
        $response = $browser->lastResponse();
        
        $response->assertRedirect('/bar');
    }
    
}