<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Event\SessionWasRotated;
use Snicco\Enterprise\Bundle\Auth\SessionRepository;

use function bin2hex;
use function hash;
use function random_bytes;
use function sleep;
use function sprintf;
use function time;

/**
 * @internal
 *
 * @psalm-suppress ArgumentTypeCoercion
 */
final class SessionRepositoryTest extends WPTestCase
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'wp_snicco_auth_sessions';

    private BetterWPDB $db;

    private TestableEventDispatcher $testable_dispatcher;

    private SessionRepository       $session_repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $this->db = BetterWPDB::fromWpdb();

        $session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            self::TABLE_NAME,
            60 * 15,
            60 * 15
        );

        SessionRepository::createTable(self::TABLE_NAME);

        $this->session_repository = $session_repository;
    }

    protected function tearDown(): void
    {
        $this->db->unprepared(sprintf('DROP TABLE IF EXISTS %s', self::TABLE_NAME));
        parent::tearDown();
    }

    /**
     * @test
     */
    public function that_a_sessions_activity_can_be_updated(): void
    {
        $hashed_token = $this->aValidSessionForUser(1);

        $activity = (int) $this->db->selectValue(
            sprintf('select last_activity from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );

        $this->assertSame(time(), $activity);

        sleep(1);

        $this->session_repository->touch($hashed_token);

        $activity = (int) $this->db->selectValue(
            sprintf('select last_activity from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );
        $this->assertSame(time(), $activity);
    }

    /**
     * @test
     */
    public function that_a_session_can_be_rotated(): void
    {
        $this->session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            self::TABLE_NAME,
            60 * 15,
            10
        );

        $hashed_token = $this->aValidSessionForUser(1);

        $next_rotation = (int) $this->db->selectValue(
            sprintf('select next_rotation_at from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );
        $this->assertSame(time() + 10, $next_rotation);

        sleep(1);

        $new_token_hashed = null;
        $this->testable_dispatcher->listen(function (SessionWasRotated $event) use (&$new_token_hashed): void {
            $this->assertSame(hash('sha256', $event->newTokenRaw()), $event->newTokenHashed());
            $new_token_hashed = $event->newTokenHashed();
        });

        $this->session_repository->rotate(1, $hashed_token);

        $this->assertIsString($new_token_hashed);
        $next_rotation = (int) $this->db->selectValue(
            sprintf('select next_rotation_at from %s where hashed_token = ?', self::TABLE_NAME),
            [$new_token_hashed]
        );
        $this->assertSame(time() + 10, $next_rotation);

        $this->expectException(NoMatchingRowFound::class);
        $this->db->selectValue(
            sprintf('select next_rotation_at from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );
    }
    
    /**
     * @test
     */
    public function that_a_rotated_session_token_proxies_to_the_newly_generated_token() :void
    {
        $this->session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            self::TABLE_NAME,
            60 * 15,
            10
        );
        
        $hashed_token_old = $this->aValidSessionForUser(1, 'foobar');
        $session_payload = $this->session_repository->getSession(1, $hashed_token_old);
        $session_payload['foo'] = 'bar';
        
        $new_token_hashed = $this->session_repository->rotate(1, $hashed_token_old);
    
        $this->session_repository->update(1,$hashed_token_old, $session_payload);
        
        $this->assertSame($session_payload, $this->session_repository->getSession(1, $hashed_token_old));
        $this->assertSame($session_payload, $this->session_repository->getSession(1, $new_token_hashed));
        
        $this->expectException(NoMatchingRowFound::class);
        $this->db->selectRow("select * from ".self::TABLE_NAME. " where hashed_token = ?", [$hashed_token_old]);
    }
    
    /**
     * @test
     */
    public function that_an_idle_session_is_deleted() :void
    {
        $this->session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            self::TABLE_NAME,
            1,
            10
        );
    
        $hashed_token = $this->aValidSessionForUser(1);
    
        sleep(2);
    
        $session = $this->session_repository->getSession(1, $hashed_token);
    
        $this->assertNull($session);
        
        $this->expectException(NoMatchingRowFound::class);
        $this->db->selectValue(
            sprintf('select * from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );
    }
    
    private function aValidSessionForUser(int $user_id, string $token_plain = null): string
    {
        $token_plain ??= bin2hex(random_bytes(16));
        $hashed_token = (string) hash('sha256', $token_plain);
        $this->session_repository->update($user_id, $hashed_token, [
            'expiration' => time() + 7200,
        ]);

        return $hashed_token;
    }
}
