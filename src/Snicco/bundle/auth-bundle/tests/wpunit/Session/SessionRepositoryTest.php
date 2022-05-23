<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Session;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Bundle\Auth\Session\Core\AuthSession;
use Snicco\Enterprise\Bundle\Auth\Session\Core\Event\SessionWasRotated;
use Snicco\Enterprise\Bundle\Auth\Session\Core\InvalidSessionToken;
use Snicco\Enterprise\Bundle\Auth\Session\Core\SessionRepository;
use Snicco\Enterprise\Bundle\Auth\Session\Core\TimeoutResolver;

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
            new TimeoutResolver(60 * 15, 60 * 15),
            self::TABLE_NAME,
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
            new TimeoutResolver(60, 10),
            self::TABLE_NAME
        );

        $hashed_token = $this->aValidSessionForUser(1);

        $last_rotation = (int) $this->db->selectValue(
            sprintf('select last_rotation from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );
        $this->assertSame(time(), $last_rotation);

        sleep(1);

        $new_token_hashed = $this->session_repository->rotate($hashed_token, 1);

        $last_rotation = (int) $this->db->selectValue(
            sprintf('select last_rotation from %s where hashed_token = ?', self::TABLE_NAME),
            [$new_token_hashed]
        );
        $this->assertSame(time(), $last_rotation);

        $this->testable_dispatcher->assertDispatched(
            fn (SessionWasRotated $event): bool => $event->newTokenHashed() === $new_token_hashed && 1 === $event->userId()
        );

        $this->expectException(NoMatchingRowFound::class);
        $this->db->selectValue(
            sprintf('select last_rotation from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );
    }

    /**
     * @test
     */
    public function that_a_rotated_session_token_proxies_to_the_newly_generated_token(): void
    {
        $this->session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            new TimeoutResolver(10, 10),
            self::TABLE_NAME,
        );

        $hashed_token_old = $this->aValidSessionForUser(1, 'foobar');
        $session = $this->session_repository->getSession($hashed_token_old);
        $new_token_hashed = $this->session_repository->rotate($hashed_token_old, 1);

        $this->session_repository->save($session);

        $this->assertEquals(
            $this->session_repository->getSession($hashed_token_old),
            $this->session_repository->getSession($new_token_hashed),
        );

        $this->expectException(NoMatchingRowFound::class);
        $this->db->selectRow('select * from ' . self::TABLE_NAME . ' where hashed_token = ?', [$hashed_token_old]);
    }

    /**
     * @test
     */
    public function that_an_idle_session_is_deleted(): void
    {
        $clock = new TestClock();

        $this->session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            new TimeoutResolver(1, 10, $clock),
            self::TABLE_NAME,
        );

        $hashed_token = $this->aValidSessionForUser(1);

        $clock->travelIntoFuture(2);

        try {
            $this->session_repository->getSession($hashed_token);

            throw new RuntimeException('Should not have found session');
        } catch (InvalidSessionToken $e) {
            $this->assertStringContainsString($hashed_token, $e->getMessage());
        }

        $this->expectException(NoMatchingRowFound::class);
        $this->db->selectValue(
            sprintf('select * from %s where hashed_token = ?', self::TABLE_NAME),
            [$hashed_token]
        );
    }

    /**
     * @test
     */
    public function that_a_sessions_is_weekly_authenticated_if_idle_and_idle_allowed(): void
    {
        $clock = new TestClock();

        $this->session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            new TimeoutResolver(1, 10, $clock, true),
            self::TABLE_NAME,
        );

        $hashed_token = $this->aValidSessionForUser(1);

        $clock->travelIntoFuture(2);

        $session = $this->session_repository->getSession($hashed_token);

        $this->assertFalse($session->fullyAuthenticated());
    }

    private function aValidSessionForUser(int $user_id, string $token_plain = null): string
    {
        $token_plain ??= bin2hex(random_bytes(16));
        $hashed_token = (string) hash('sha256', $token_plain);
        $expires_at = time() + 7200;

        $session = new AuthSession($hashed_token, $user_id, [
            'expiration' => $expires_at,
        ]);

        $this->session_repository->save($session);

        return $hashed_token;
    }
}
