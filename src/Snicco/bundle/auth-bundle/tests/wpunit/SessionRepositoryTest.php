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

        $session_repository->createTable();

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

        $this->session_repository->rotate($hashed_token);

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

    private function aValidSessionForUser(int $user_id): string
    {
        $hashed_token = (string) hash('sha256', bin2hex(random_bytes(16)));
        $this->session_repository->update($user_id, $hashed_token, [
            'expiration' => time() + 7200,
        ]);

        return $hashed_token;
    }
}
