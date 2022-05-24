<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\usecase\Session;

use RuntimeException;
use Codeception\Test\Unit;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\AuthSession;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\TimeoutConfig;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\SessionManager;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemorySessionRepository;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Event\SessionWasRotated;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Event\SessionRotationTimeout;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Exception\InvalidSessionToken;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Event\SessionIdleTimeout;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Event\AllowWeakAuthenticationForIdleSession;

use function hash;
use function time;
use function bin2hex;
use function array_merge;
use function array_replace;

final class SessionManagerTest extends Unit
{
    
    private TestClock $clock;
    private SessionManager $session_manager;
    private InMemorySessionRepository $session_repo;
    private int $idle_interval;
    private int $rotation_interval;
    private TestableEventDispatcher $testable_dispatcher;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->clock = new TestClock();
        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $this->idle_interval = 5;
        $this->rotation_interval = 7;
        
        $this->session_manager = new SessionManager(
            $this->testable_dispatcher,
            new TimeoutConfig($this->idle_interval, $this->rotation_interval, false),
            $this->session_repo = new InMemorySessionRepository($this->clock),
            $this->clock,
        );
    }
    
    /**
     * @test
     */
    public function that_a_sessions_is_not_retrieved_if_its_idle() :void
    {
        $session = $this->aPersistedSessionForUser(1);
        
        $this->clock->travelIntoFuture($this->idle_interval);
        
        $this->assertEquals($session, $this->session_manager->getSession($session->hashedToken()));
        
        $this->clock->travelIntoFuture(1);
        
        try {
            $this->session_manager->getSession($session->hashedToken());
            throw new RuntimeException('Should have failed for idle session');
        } catch (InvalidSessionToken $e) {
        }
        
        // Deleted in the session repo
        try {
            $this->session_repo->getSession($session->hashedToken());
            throw new RuntimeException('Idle session should have been deleted.');
        } catch (InvalidSessionToken $e) {
        }
    }
    
    /**
     * @test
     */
    public function that_an_event_is_dispatched_if_a_session_is_idle() :void
    {
        $session = $this->aPersistedSessionForUser(1);
        
        $this->clock->travelIntoFuture($this->idle_interval + 1);
        
        try {
            $this->session_manager->getSession($session->hashedToken());
            throw new RuntimeException("Should have failed for idle session");
        } catch (InvalidSessionToken $e) {
        }
        
        $this->testable_dispatcher->assertDispatched(function (SessionWasIdle $e) use ($session) {
            return $e->hashed_token === $session->hashedToken() && $e->user_id === $session->userId();
        });
    }
    
    /**
     * @test
     */
    public function that_a_session_idle_timeout_can_be_customized_with_events() :void
    {
        $session_manager = new SessionManager(
            $this->testable_dispatcher,
            new TimeoutConfig($this->idle_interval, $this->rotation_interval),
            $this->session_repo = new InMemorySessionRepository($this->clock),
            $this->clock,
        );
        
        $session_token = $this->aPersistedSessionForUser(1)->hashedToken();
        
        $this->assertTrue($session_manager->getSession($session_token)->isFullyAuthenticated());
        
        $this->clock->travelIntoFuture($this->idle_interval + 1);
        
        $this->testable_dispatcher->listen(function (SessionIdleTimeout $event) {
            $this->assertSame($this->idle_interval, $event->default_timeout_in_seconds);
            $event->idle_timeout_in_seconds++;
        });
        
        $this->assertTrue($session_manager->getSession($session_token)->isFullyAuthenticated());
        // Still in repo
        $this->session_repo->getSession($session_token);
        
        $this->testable_dispatcher->assertNotDispatched(SessionWasIdle::class);
    }
    
    /**
     * @test
     */
    public function that_weak_authentication_can_be_customized_with_events() :void
    {
        $session_token = $this->aPersistedSessionForUser(1)->hashedToken();
        
        $this->assertTrue($this->session_manager->getSession($session_token)->isFullyAuthenticated());
        
        $this->testable_dispatcher->listen(function (AllowWeakAuthenticationForIdleSession $event) {
            $event->allow = true;
        });
        
        $this->clock->travelIntoFuture($this->idle_interval + 1);
        
        // Weak auth
        $this->assertFalse($this->session_manager->getSession($session_token)->isFullyAuthenticated());
        // But still in repo
        $this->session_repo->getSession($session_token);
        
        $this->testable_dispatcher->assertNotDispatched(SessionWasIdle::class);
    }
    
    /**
     * @test
     */
    public function that_a_session_is_rotated_if_needed() :void
    {
        $session = $this->aPersistedSessionForUser(
            1,
            10,
            [],
            $this->clock->currentTimestamp() - $this->rotation_interval - 1
        );
        
        $retrieved = $this->session_manager->getSession($session->hashedToken());
        
        $this->assertNotSame($retrieved->hashedToken(), $session->hashedToken());
        $this->assertSame($retrieved->lastActivity(), $session->lastActivity());
        $this->assertSame($retrieved->data(), $session->data());
        $this->assertSame($retrieved->isFullyAuthenticated(), $session->isFullyAuthenticated());
        
        $this->testable_dispatcher->assertDispatched(SessionWasRotated::class);
    }
    
    /**
     * @test
     */
    public function that_session_rotation_can_be_customized_with_events() :void
    {
        $session = $this->aPersistedSessionForUser(
            1,
            10,
            [],
            $this->clock->currentTimestamp() - $this->rotation_interval - 1
        );
    
        $this->testable_dispatcher->listen(function (SessionRotationTimeout $event) {
            $event->rotation_timeout_in_seconds++;
        });
        
        $retrieved = $this->session_manager->getSession($session->hashedToken());
    
        $this->assertSame($retrieved->hashedToken(), $session->hashedToken());
        $this->assertSame($retrieved->lastActivity(), $session->lastActivity());
        $this->assertSame($retrieved->data(), $session->data());
        $this->assertSame($retrieved->isFullyAuthenticated(), $session->isFullyAuthenticated());
    
        $this->testable_dispatcher->assertNotDispatched(SessionWasRotated::class);
    }
    
    /**
     * @test
     */
    public function that_a_rotated_session_can_be_retrieved_and_updated_by_the_old_session_token() :void
    {
        $old_token = $this->aPersistedSessionForUser(
            1,
            10,
            [],
            $this->clock->currentTimestamp() - $this->rotation_interval - 1
        )->hashedToken();
        
        $retrieved = $this->session_manager->getSession($old_token);
    
        $this->assertNotSame($retrieved->hashedToken(), $old_token);
      
        $this->assertEquals($retrieved, $this->session_manager->getSession($old_token));
        
        $this->session_manager->save($s = new AuthSession(
            $old_token,
            $retrieved->userId(),
            $retrieved->lastActivity(),
            $retrieved->lastRotation(),
            array_merge($retrieved->data(), ['foo' => 'bar']),
        ));
    
        // Session token is not in the session repo but in memory.
        try {
            $this->session_repo->getSession($old_token);
            throw new RuntimeException("Rotated session token should not be persisted to the session repo.");
        }catch (InvalidSessionToken $e) {
        
        }
        
        $this->assertEquals($s->withToken($retrieved->hashedToken()) , $this->session_manager->getSession($old_token));
    }
    
    private function aPersistedSessionForUser(
        int $user_id,
        int $expires_in = 10,
        array $data = [],
        int $last_rotation = null,
        string $token_plain = null
    ) :AuthSession {
        $s = $this->aNonPersistedSessionForUser($user_id, $expires_in, $data, $last_rotation, $token_plain);
        $this->session_repo->save($s);
        return $s;
    }
    
    private function aNonPersistedSessionForUser(
        int $user_id,
        int $expires_in = 10,
        array $data = [],
        int $last_rotation = null,
        string $token_plain = null
    ) :AuthSession {
        $token_plain ??= bin2hex(random_bytes(16));
        $hashed_token = (string)hash('sha256', $token_plain);
        $expires_at = time() + $expires_in;
        
        return new AuthSession(
            $hashed_token, $user_id, time(), $last_rotation ? : time(), array_replace($data, [
                'expiration' => $expires_at,
            ])
        );
    }
    
}