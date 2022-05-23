<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\acceptance\Session;

use Codeception\Test\Unit;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\AuthSession;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\TimeoutConfig;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemorySessionRepository;

use Snicco\Enterprise\Bundle\Auth\Session\Application\SessionCommandHandler;

use Snicco\Enterprise\Bundle\Auth\Session\Application\DestroyAllSessions\DestroyAllSessions;

use Snicco\Enterprise\Bundle\Auth\Session\Application\RemoveExpiredSessions\RemoveExpiredSessions;

use function hash;
use function time;

final class DestroyAllSessionsTest extends Unit
{
    
    private TestClock $clock;
    private SessionManager $session_manager;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->clock = new TestClock();
        $this->session_manager = new SessionManager(
            new BaseEventDispatcher(),
            new TimeoutConfig(10,10),
            new InMemorySessionRepository($this->clock)
        );
    }
    
    /**
     * @test
     */
    public function that_all_sessions_can_be_destroyed() :void
    {
        $this->session_manager->save(new AuthSession(
            (string) hash('sha256', 'foo'),
            1,
            time(),
            time(),
             ['expiration' => time() +10 ]
        ));
    
        $this->session_manager->save(new AuthSession(
            (string) hash('sha256', 'bar'),
            1,
            time(),
            time(),
            ['expiration' => time()+10 ]
        ));
    
        $this->session_manager->save(new AuthSession(
            (string) hash('sha256', 'baz'),
            2,
            time(),
            time(),
            ['expiration' => time()+10 ]
        ));
        
        $this->assertCount(2, $this->session_manager->getAllForUser(1));
        $this->assertCount(1, $this->session_manager->getAllForUser(2));

        $handler = new SessionCommandHandler($this->session_manager);
        
        $handler->destroyAllSessions(new DestroyAllSessions());
    
        $this->assertCount(0, $this->session_manager->getAllForUser(1));
        $this->assertCount(0, $this->session_manager->getAllForUser(2));
    }
    
  
    
}