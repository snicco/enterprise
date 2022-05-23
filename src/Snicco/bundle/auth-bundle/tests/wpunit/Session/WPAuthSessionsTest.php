<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Session;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Bundle\Auth\Session\Core\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Auth\Session\Core\Event\SessionWasRotated;
use Snicco\Enterprise\Bundle\Auth\Session\Core\SessionRepository;
use Snicco\Enterprise\Bundle\Auth\Session\Core\TimeoutResolver;
use Snicco\Enterprise\Bundle\Auth\Session\Core\WPAuthSessions;
use stdClass;

use function add_filter;
use function base64_encode;
use function hash;
use function remove_all_filters;
use function serialize;
use function sleep;
use function time;

/**
 * @internal
 */
final class WPAuthSessionsTest extends WPTestCase
{
    private BetterWPDB $db;

    /**
     * @var non-empty-string
     */
    private string $table_name = 'wp_snicco_auth_sessions';

    private TestableEventDispatcher $testable_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $this->db = BetterWPDB::fromWpdb();

        $session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            new TimeoutResolver(60 * 15, 60 * 16),
            $this->table_name,
        );

        SessionRepository::createTable($this->table_name);

        WPAuthSessions::setSessionRepository($session_repository);
        add_filter('session_token_manager', fn (): string => WPAuthSessions::class, 9999999);
    }

    protected function tearDown(): void
    {
        $this->db->unprepared("DROP TABLE IF EXISTS {$this->table_name}");
        parent::tearDown();
        remove_all_filters('session_token_manager');
    }

    /**
     * @test
     */
    public function that_sessions_are_saved_to_a_custom_table(): void
    {
        $session_tokens = WPAuthSessions::get_instance(1);

        add_filter('attach_session_information', function () {
            return [
                'foo' => 'bar',
            ];
        });

        $token = $session_tokens->create(time() + 1000);

        $count = $this->db->selectValue("select count(*) from {$this->table_name} where `user_id` = ?", [1]);
        $this->assertSame(1, $count);

        $session = $session_tokens->get($token);
        $this->assertIsArray($session);
        $this->assertArrayHasKey('foo', $session);
    }

    /**
     * @test
     */
    public function that_objects_can_be_stored_in_the_session(): void
    {
        $session_tokens = WPAuthSessions::get_instance(1);

        $obj = new stdClass();
        $obj->foo = 'bar';

        add_filter('attach_session_information', fn (): array => [
            'object' => $obj,
        ]);

        $token = $session_tokens->create(time() + 1000);

        $session = $session_tokens->get($token);
        $this->assertIsArray($session);
        $this->assertArrayHasKey('object', $session);
        $this->assertEquals($obj, $session['object'] ?? null);
    }

    /**
     * @test
     */
    public function that_a_non_existing_token_returns_null(): void
    {
        $sessions = WPAuthSessions::get_instance(1);
        $session = $sessions->get('foobar');
        $this->assertNull($session);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_a_non_array_payload(): void
    {
        $sessions = WPAuthSessions::get_instance(1);

        $token = $sessions->create(time() + 10);

        $this->db->update(
            $this->table_name,
            [
                'hashed_token' => hash('sha256', $token),
            ],
            [
                'payload' => base64_encode(serialize('foo')),
            ]
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Got: string');

        $sessions->get($token);
    }

    /**
     * @test
     */
    public function that_a_session_can_be_destroyed(): void
    {
        $sessions = WPAuthSessions::get_instance(1);

        $token = $sessions->create(time() + 10);

        /** @var int $count */
        $count = $this->db->selectValue("select count(*) from {$this->table_name} where `user_id` = ?", [1]);
        $this->assertSame(1, $count);

        $sessions->destroy($token);

        /** @var int $count */
        $count = $this->db->selectValue("select count(*) from {$this->table_name} where `user_id` = ?", [1]);
        $this->assertSame(0, $count);
    }

    /**
     * @test
     */
    public function that_all_sessions_for_a_user_can_be_retrieved(): void
    {
        add_filter('attach_session_information', function () {
            return [
                'foo' => 'bar',
            ];
        });

        $sessions_1 = WPAuthSessions::get_instance(1);

        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessions::get_instance(2);

        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);

        $this->assertCount(2, $all_1 = $sessions_1->get_all());
        /** @var array $session */
        foreach ($all_1 as $session) {
            $this->assertArrayHasKey('foo', $session);
        }

        $this->assertCount(3, $all_2 = $sessions_2->get_all());
        /** @var array $session */
        foreach ($all_2 as $session) {
            $this->assertArrayHasKey('foo', $session);
        }
    }

    /**
     * @test
     */
    public function that_all_sessions_for_a_user_can_be_destroyed(): void
    {
        $sessions_1 = WPAuthSessions::get_instance(1);

        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessions::get_instance(2);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);

        $this->assertCount(2, $sessions_1->get_all());
        $this->assertCount(3, $sessions_2->get_all());

        $sessions_1->destroy_all();

        $this->assertCount(0, $sessions_1->get_all());
        $this->assertCount(3, $sessions_2->get_all());
    }

    /**
     * @test
     */
    public function that_all_sessions_besides_one_can_be_destroyed_for_a_user(): void
    {
        $sessions_1 = WPAuthSessions::get_instance(1);

        $token = $sessions_1->create(time() + 10);
        $token_deleted = $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessions::get_instance(2);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);

        $this->assertCount(2, $sessions_1->get_all());
        $this->assertCount(3, $sessions_2->get_all());

        $sessions_1->destroy_others($token);

        $this->assertCount(1, $sessions_1->get_all());
        $this->assertIsArray($sessions_1->get($token));
        $this->assertNull($sessions_1->get($token_deleted));

        $this->assertCount(3, $sessions_2->get_all());
    }

    /**
     * @test
     */
    public function that_all_sessions_for_all_users_can_be_dropped(): void
    {
        $sessions_1 = WPAuthSessions::get_instance(1);

        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessions::get_instance(2);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);

        WPAuthSessions::drop_sessions();

        $this->assertCount(0, $sessions_1->get_all());
        $this->assertCount(0, $sessions_2->get_all());
    }

    /**
     * @test
     */
    public function that_expired_sessions_are_not_included(): void
    {
        $sessions_1 = WPAuthSessions::get_instance(1);

        $token = $sessions_1->create(time() + 1);

        $this->assertIsArray($sessions_1->get($token));

        sleep(2);

        $this->assertNull($sessions_1->get($token));
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_a_session_is_created_without_expiration(): void
    {
        $sessions_1 = WPAuthSessions::get_instance(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expiration');

        $sessions_1->update((string) hash('sha256', 'foo'), []);
    }

    /**
     * @test
     */
    public function that_an_idle_session_is_not_included(): void
    {
        $clock = new TestClock();
        $now = $clock->currentTimestamp();

        $session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            new TimeoutResolver(1, 1, $clock),
            $this->table_name,
        );
        WPAuthSessions::setSessionRepository($session_repository);

        $sessions_1 = WPAuthSessions::get_instance(10);
        $token = $sessions_1->create($now + 7200);

        $this->assertIsArray($sessions_1->get($token));

        $clock->travelIntoFuture(2);

        $this->assertNull($sessions_1->get($token));
        $this->testable_dispatcher->assertDispatched(function (SessionWasIdle $event) use ($token) {
            return 10 === $event->user_id && hash('sha256', $token) === $event->hashed_token;
        });
    }

    /**
     * @test
     */
    public function that_idle_sessions_are_included_if_weekly_idle_sessions_are_allowed(): void
    {
        $clock = new TestClock();
        $now = $clock->currentTimestamp();

        $session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            new TimeoutResolver(1, 1, $clock, true),
            $this->table_name,
        );
        WPAuthSessions::setSessionRepository($session_repository);

        $sessions_1 = WPAuthSessions::get_instance(10);
        $token = $sessions_1->create($now + 7200);

        $this->assertIsArray($sessions_1->get($token));

        $clock->travelIntoFuture(2);

        $this->assertIsArray($sessions_1->get($token));
        $this->testable_dispatcher->assertNotDispatched(SessionWasIdle::class);
    }

    /**
     * @test
     */
    public function that_an_event_is_fired_if_a_sessions_rotation_interval_is_exceeded(): void
    {
        $session_repository = new SessionRepository(
            $this->testable_dispatcher,
            $this->db,
            new TimeoutResolver(10, 1),
            $this->table_name,
        );
        WPAuthSessions::setSessionRepository($session_repository);

        $sessions_1 = WPAuthSessions::get_instance(10);
        $token = $sessions_1->create(time() + 7200);

        $this->assertIsArray($sessions_1->get($token));

        $this->testable_dispatcher->assertNotDispatched(SessionWasRotated::class);

        sleep(2);

        $this->assertIsArray($sessions_1->get($token));

        $this->testable_dispatcher->assertDispatched(
            fn (SessionWasRotated $event) => 10 === $event->userId()
        );
    }
}
