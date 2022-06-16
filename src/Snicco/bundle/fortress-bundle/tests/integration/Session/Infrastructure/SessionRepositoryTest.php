<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration\Session\Infrastructure;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\AuthSession;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Exception\InvalidSessionToken;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\SessionRepository;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\SessionRepositoryBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\InMemorySessionRepository;
use WP_User;

use function array_replace;
use function bin2hex;
use function get_user_by;
use function hash;
use function implode;
use function is_int;
use function random_bytes;
use function time;
use function wp_create_user;
use function wp_delete_user;

/**
 * @internal
 */
final class SessionRepositoryTest extends WPTestCase
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'wp_snicco_auth_sessions';

    private int $calvin_id;

    private int $marlon_id;

    protected function setUp(): void
    {
        parent::setUp();
        SessionRepositoryBetterWPDB::createTable(BetterWPDB::fromWpdb(), self::TABLE_NAME);
        $this->calvin_id = (new WP_User(1))->ID;
        $marlon = get_user_by('login', 'marlon');
        if ($marlon) {
            $this->marlon_id = $marlon->ID;
        } else {
            $marlon_id = wp_create_user('marlon', 'foobar');
            if (! is_int($marlon_id)) {
                /** @var string[] $messages */
                $messages = $marlon_id->get_error_messages();

                throw new RuntimeException(implode(',', $messages));
            }

            $this->marlon_id = $marlon_id;
        }
    }

    protected function tearDown(): void
    {
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS ' . self::TABLE_NAME);
        $res = wp_delete_user($this->marlon_id);
        if (! $res) {
            throw new RuntimeException('Could not delete user');
        }

        parent::tearDown();
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_session_can_be_stored_and_retrieved(Closure $session_repo): void
    {
        $session_repo = $session_repo(new TestClock());
        $session = $this->aNonPersistedSessionForUser(1);

        try {
            $session_repo->getSession($session->hashedToken());

            throw new RuntimeException('Should throw exception for invalid token');
        } catch (InvalidSessionToken $e) {
        }

        $session_repo->save($session);

        $stored_session = $session_repo->getSession($session->hashedToken());

        $this->assertEquals($session, $stored_session);
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_an_expired_session_is_not_included(Closure $session_repo): void
    {
        $session_repo = $session_repo($clock = new TestClock());
        $session = $this->aNonPersistedSessionForUser($this->calvin_id, 2);

        $session_repo->save($session);

        $session_repo->getSession($session->hashedToken());

        $clock->travelIntoFuture(1);

        $session_repo->getSession($session->hashedToken());

        $clock->travelIntoFuture(1);

        $session_repo->getSession($session->hashedToken());

        $clock->travelIntoFuture(1);

        $this->expectException(InvalidSessionToken::class);

        $session_repo->getSession($session->hashedToken());
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_all_sessions_for_a_user_can_be_retrieved(Closure $session_repo): void
    {
        $session_repo = $session_repo(new TestClock());
        $calvin_session1 = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);
        $calvin_session2 = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);

        $marlon_session1 = $this->aPersistedSessionForUser($session_repo, $this->marlon_id);

        $this->assertEquals([
            $calvin_session1->hashedToken() => [
                'expires_at' => $calvin_session1->expiresAt(),
                'last_rotation' => $calvin_session1->lastRotation(),
                'last_activity' => $calvin_session1->lastActivity(),
                'data' => $calvin_session1->data(),
            ],
            $calvin_session2->hashedToken() => [
                'expires_at' => $calvin_session2->expiresAt(),
                'last_rotation' => $calvin_session2->lastRotation(),
                'last_activity' => $calvin_session2->lastActivity(),
                'data' => $calvin_session2->data(),
            ],
        ], $session_repo->getAllForUser($this->calvin_id));

        $this->assertEquals([
            $marlon_session1->hashedToken() => [
                'expires_at' => $marlon_session1->expiresAt(),
                'last_rotation' => $marlon_session1->lastRotation(),
                'last_activity' => $marlon_session1->lastActivity(),
                'data' => $marlon_session1->data(),
            ],
        ], $session_repo->getAllForUser($this->marlon_id));
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_expired_sessions_are_not_included_in_all_sessions(Closure $session_repo): void
    {
        $session_repo = $session_repo($clock = new TestClock());
        $calvin_session1 = $this->aPersistedSessionForUser($session_repo, $this->calvin_id, 9);
        $calvin_session2 = $this->aPersistedSessionForUser($session_repo, $this->calvin_id, 10);

        $clock->travelIntoFuture(9);

        $this->assertEquals([
            $calvin_session1->hashedToken() => [
                'expires_at' => $calvin_session1->expiresAt(),
                'last_activity' => $calvin_session1->lastActivity(),
                'last_rotation' => $calvin_session1->lastRotation(),
                'data' => $calvin_session1->data(),
            ],
            $calvin_session2->hashedToken() => [
                'expires_at' => $calvin_session2->expiresAt(),
                'last_activity' => $calvin_session2->lastActivity(),
                'last_rotation' => $calvin_session2->lastRotation(),
                'data' => $calvin_session2->data(),
            ],
        ], $session_repo->getAllForUser($this->calvin_id));

        $clock->travelIntoFuture(1);

        $this->assertEquals([
            $calvin_session2->hashedToken() => [
                'expires_at' => $calvin_session2->expiresAt(),
                'last_activity' => $calvin_session2->lastActivity(),
                'last_rotation' => $calvin_session2->lastRotation(),
                'data' => $calvin_session2->data(),
            ],
        ], $session_repo->getAllForUser($this->calvin_id));

        $clock->travelIntoFuture(1);

        $this->assertEquals([
        ], $session_repo->getAllForUser($this->calvin_id));
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_a_session_can_be_deleted(Closure $session_repo): void
    {
        $session_repo = $session_repo(new TestClock());

        $session_new = $this->aNonPersistedSessionForUser($this->calvin_id);
        $persisted_session_for_user = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);

        try {
            $session_repo->delete($session_new->hashedToken());

            throw new RuntimeException('Should throw exception if deleting non-saved session');
        } catch (InvalidSessionToken $e) {
        }

        $this->assertEquals(
            $persisted_session_for_user,
            $session_repo->getSession($persisted_session_for_user->hashedToken())
        );

        $session_repo->delete($persisted_session_for_user->hashedToken());

        $this->expectException(InvalidSessionToken::class);
        $session_repo->getSession($persisted_session_for_user->hashedToken());
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_all_sessions_for_a_user_can_be_destroyed(Closure $session_repo): void
    {
        $session_repo = $session_repo(new TestClock());
        $calvin_session1 = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);
        $calvin_session2 = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);

        $marlon_session1 = $this->aPersistedSessionForUser($session_repo, $this->marlon_id);

        $this->assertEquals([
            $calvin_session1->hashedToken() => [
                'expires_at' => $calvin_session1->expiresAt(),
                'last_activity' => $calvin_session1->lastActivity(),
                'last_rotation' => $calvin_session1->lastRotation(),
                'data' => $calvin_session1->data(),
            ],
            $calvin_session2->hashedToken() => [
                'expires_at' => $calvin_session2->expiresAt(),
                'last_activity' => $calvin_session2->lastActivity(),
                'last_rotation' => $calvin_session2->lastRotation(),
                'data' => $calvin_session2->data(),
            ],
        ], $session_repo->getAllForUser($this->calvin_id));

        $this->assertEquals([
            $marlon_session1->hashedToken() => [
                'expires_at' => $marlon_session1->expiresAt(),
                'last_activity' => $marlon_session1->lastActivity(),
                'last_rotation' => $marlon_session1->lastRotation(),
                'data' => $marlon_session1->data(),
            ],
        ], $session_repo->getAllForUser($this->marlon_id));

        $session_repo->destroyAllSessionsForUser($this->calvin_id);

        $this->assertEquals([
        ], $session_repo->getAllForUser($this->calvin_id));

        $this->assertEquals([
            $marlon_session1->hashedToken() => [
                'expires_at' => $marlon_session1->expiresAt(),
                'last_activity' => $marlon_session1->lastActivity(),
                'last_rotation' => $marlon_session1->lastRotation(),
                'data' => $marlon_session1->data(),
            ],
        ], $session_repo->getAllForUser($this->marlon_id));
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_all_sessions_for_a_user_except_one_can_be_destroyed(Closure $session_repo): void
    {
        $session_repo = $session_repo(new TestClock());
        $calvin_session1 = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);
        $this->aPersistedSessionForUser($session_repo, $this->calvin_id);

        $this->assertCount(2, $session_repo->getAllForUser($this->calvin_id));

        $marlon_session1 = $this->aPersistedSessionForUser($session_repo, $this->marlon_id);

        $session_repo->destroyOtherSessionsForUser($this->calvin_id, $calvin_session1->hashedToken());

        $this->assertEquals([
            $calvin_session1->hashedToken() => [
                'expires_at' => $calvin_session1->expiresAt(),
                'last_activity' => $calvin_session1->lastActivity(),
                'last_rotation' => $calvin_session1->lastRotation(),
                'data' => $calvin_session1->data(),
            ],
        ], $session_repo->getAllForUser($this->calvin_id));

        $this->assertEquals([
            $marlon_session1->hashedToken() => [
                'expires_at' => $marlon_session1->expiresAt(),
                'last_activity' => $marlon_session1->lastActivity(),
                'last_rotation' => $marlon_session1->lastRotation(),
                'data' => $marlon_session1->data(),
            ],
        ], $session_repo->getAllForUser($this->marlon_id));
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_all_sessions_can_be_destroyed(Closure $session_repo): void
    {
        $session_repo = $session_repo(new TestClock());
        $this->aPersistedSessionForUser($session_repo, $this->calvin_id);
        $this->aPersistedSessionForUser($session_repo, $this->calvin_id);
        $this->aPersistedSessionForUser($session_repo, $this->marlon_id);

        $session_repo->destroyAll();

        $this->assertEquals([], $session_repo->getAllForUser($this->calvin_id));
        $this->assertEquals([], $session_repo->getAllForUser($this->marlon_id));
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_a_session_can_be_rotated(Closure $session_repo): void
    {
        $session_repo = $session_repo($clock = new TestClock());

        $session_old = $session_repo->getSession(
            $this->aPersistedSessionForUser($session_repo, $this->calvin_id, 10, [
                'foo' => 'bar',
            ])->hashedToken()
        );

        $now = $clock->currentTimestamp();
        $clock->travelIntoFuture(3);

        $session_repo->rotateToken(
            $session_old->hashedToken(),
            $token_new = $this->newToken('foobar'),
            $clock->currentTimestamp()
        );

        try {
            $session_repo->getSession($session_old->hashedToken());

            throw new RuntimeException('Old tokens should be deleted after rotating them');
        } catch (InvalidSessionToken $e) {
        }

        try {
            $session_repo->rotateToken(
                $session_old->hashedToken(),
                $token_new,
                $clock->currentTimestamp()
            );

            throw new RuntimeException('Should not be able to rotate missing session');
        } catch (InvalidSessionToken $e) {
        }

        $new_session = $session_repo->getSession($token_new);

        $this->assertSame($token_new, $new_session->hashedToken());
        $this->assertEquals($session_old->data(), $new_session->data());
        $this->assertSame($session_old->expiresAt(), $new_session->expiresAt());
        $this->assertSame($now + 3, $new_session->lastRotation());
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_sessions_activity_can_be_updated(Closure $session_repo): void
    {
        $session_repo = $session_repo($clock = new TestClock());

        $session = $session_repo->getSession(
            $this->aPersistedSessionForUser($session_repo, $this->calvin_id)
                ->hashedToken()
        );
        $now = $clock->currentTimestamp();
        $this->assertSame($now, $session->lastActivity());

        $clock->travelIntoFuture(10);

        try {
            $session_repo->updateActivity($this->newToken('foobar'));

            throw new RuntimeException('Should throw exception for missing token');
        } catch (InvalidSessionToken $e) {
        }

        $session_repo->updateActivity($session->hashedToken());

        $this->assertSame($now + 10, $session_repo->getSession($session->hashedToken())->lastActivity());
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_saving_a_sessions_updates_its_activity(Closure $session_repo): void
    {
        $session_repo = $session_repo($clock = new TestClock());

        $session = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);
        $now = $clock->currentTimestamp();
        $this->assertSame($now, $session->lastActivity());

        $clock->travelIntoFuture(10);

        $session_repo->save($session);

        $this->assertSame($now + 10, $session_repo->getSession($session->hashedToken())->lastActivity());
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_updating_a_sessions_activity_does_not_change_the_last_rotation_value(
        Closure $session_repo
    ): void {
        $session_repo = $session_repo($clock = new TestClock());

        $session = $this->aPersistedSessionForUser($session_repo, $this->calvin_id);
        $now = $clock->currentTimestamp();
        $this->assertSame($now, $session->lastActivity());

        $clock->travelIntoFuture(10);

        $session_repo->save($session);

        $this->assertSame($now, $session_repo->getSession($session->hashedToken())->lastRotation());
    }

    /**
     * @test
     *
     * @param Closure(Clock): SessionRepository $session_repo
     *
     * @dataProvider sessionRepos
     */
    public function that_expired_sessions_can_be_garbage_collected(Closure $session_repo): void
    {
        $session_repo = $session_repo($clock = new TestClock());

        $this->aPersistedSessionForUser($session_repo, $this->calvin_id, 5);
        $this->aPersistedSessionForUser($session_repo, $this->calvin_id, 4);
        $this->aPersistedSessionForUser($session_repo, $this->calvin_id, 3);

        $this->aPersistedSessionForUser($session_repo, $this->marlon_id, 5);
        $this->aPersistedSessionForUser($session_repo, $this->marlon_id, 4);
        $this->aPersistedSessionForUser($session_repo, $this->marlon_id, 3);

        $clock->travelIntoFuture(3);

        $session_repo->gc();

        $this->assertCount(3, $session_repo->getAllForUser($this->calvin_id));
        $this->assertCount(3, $session_repo->getAllForUser($this->marlon_id));

        $clock->travelIntoFuture(1);

        $session_repo->gc();

        $this->assertCount(2, $session_repo->getAllForUser($this->calvin_id));
        $this->assertCount(2, $session_repo->getAllForUser($this->marlon_id));

        $clock->travelIntoFuture(1);

        $session_repo->gc();

        $this->assertCount(1, $session_repo->getAllForUser($this->calvin_id));
        $this->assertCount(1, $session_repo->getAllForUser($this->marlon_id));

        $clock->travelIntoFuture(1);

        $session_repo->gc();

        $this->assertCount(0, $session_repo->getAllForUser($this->calvin_id));
        $this->assertCount(0, $session_repo->getAllForUser($this->marlon_id));
    }

    public function sessionRepos(): Generator
    {
        yield 'memory' => [
            fn (Clock $clock): InMemorySessionRepository => new InMemorySessionRepository($clock),
        ];

        yield 'better-wpdb' => [
            fn (Clock $clock): SessionRepositoryBetterWPDB => new SessionRepositoryBetterWPDB(
                BetterWPDB::fromWpdb(),
                self::TABLE_NAME,
                $clock
            ),
        ];
    }

    private function aPersistedSessionForUser(
        SessionRepository $repo,
        int $user_id,
        int $expires_in = 10,
        array $data = [],
        string $token_plain = null
    ): AuthSession {
        $s = $this->aNonPersistedSessionForUser($user_id, $expires_in, $data, $token_plain);
        $repo->save($s);

        return $s;
    }

    private function aNonPersistedSessionForUser(
        int $user_id,
        int $expires_in = 10,
        array $data = [],
        string $token_plain = null
    ): AuthSession {
        $token_plain ??= bin2hex(random_bytes(16));
        $hashed_token = (string) hash('sha256', $token_plain);
        $expires_at = time() + $expires_in;

        return new AuthSession(
            $hashed_token,
            $user_id,
            time(),
            time(),
            array_replace($data, [
                'expiration' => $expires_at,
            ])
        );
    }

    private function newToken(string $token): string
    {
        return (string) hash('sha256', $token);
    }
}
