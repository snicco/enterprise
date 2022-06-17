<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration\Session\Infrastructure;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\TimeoutConfig;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\SessionRepositoryBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\WPAuthSessionTokens;
use stdClass;
use WP_User;

use function add_filter;
use function base64_encode;
use function hash;
use function implode;
use function is_int;
use function remove_all_filters;
use function serialize;
use function sleep;
use function time;
use function wp_create_user;
use function wp_delete_user;

/**
 * @internal
 */
final class WPAuthSessionTokensTest extends WPTestCase
{
    private BetterWPDB $db;

    /**
     * @var non-empty-string
     */
    private string $table_name = 'wp_snicco_auth_sessions';

    private TestableEventDispatcher $testable_dispatcher;

    private int $other_user_id;

    private int $default_user_id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $this->db = BetterWPDB::fromWpdb();

        SessionRepositoryBetterWPDB::createTable($this->db, $this->table_name);

        $session_repository = new SessionRepositoryBetterWPDB(
            $this->db,
            $this->table_name,
        );

        WPAuthSessionTokens::setSessionManager(
            new SessionManager(
                $this->testable_dispatcher,
                new TimeoutConfig(10, 10),
                $session_repository
            )
        );
        add_filter('session_token_manager', fn (): string => WPAuthSessionTokens::class, 9_999_999);

        $this->default_user_id = (new WP_User(1))->ID;
        $other_user_id = wp_create_user('foo', 'bar');
        if (! is_int($other_user_id)) {
            /** @var string[] $errors */
            $errors = $other_user_id->get_error_messages();

            throw new RuntimeException(implode(',', $errors));
        }
        $this->other_user_id = $other_user_id;
    }

    protected function tearDown(): void
    {
        $this->db->unprepared("DROP TABLE IF EXISTS {$this->table_name}");
        parent::tearDown();
        remove_all_filters('session_token_manager');
        wp_delete_user($this->other_user_id);
    }

    /**
     * @test
     */
    public function that_objects_can_be_stored_in_the_session(): void
    {
        $session_tokens = WPAuthSessionTokens::get_instance(1);

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
        $sessions = WPAuthSessionTokens::get_instance(1);
        $session = $sessions->get('foobar');
        $this->assertNull($session);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_a_non_array_payload(): void
    {
        $sessions = WPAuthSessionTokens::get_instance(1);

        $this->db->insert(
            $this->table_name,
            [
                'hashed_token' => hash('sha256', 'foobar'),
                'payload' => base64_encode(serialize('foo')),
                'user_id' => 1,
                'expires_at' => time() + 10,
            ],
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Got: string');

        $sessions->get('foobar');
    }

    /**
     * @test
     */
    public function that_a_session_can_be_destroyed(): void
    {
        $sessions = WPAuthSessionTokens::get_instance(1);

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
    public function that_a_session_can_be_destroyed_by_calling_update_without_data(): void
    {
        $sessions = WPAuthSessionTokens::get_instance(1);

        $token = $sessions->create(time() + 10);

        /** @var int $count */
        $count = $this->db->selectValue("select count(*) from {$this->table_name} where `user_id` = ?", [1]);
        $this->assertSame(1, $count);

        /** @psalm-suppress NullArgument */
        $sessions->update($token, null);

        /** @var int $count */
        $count = $this->db->selectValue("select count(*) from {$this->table_name} where `user_id` = ?", [1]);
        $this->assertSame(0, $count);
    }

    /**
     * @test
     */
    public function that_all_sessions_for_a_user_can_be_retrieved(): void
    {
        add_filter('attach_session_information', fn (): array => [
            'foo' => 'bar',
        ]);

        $sessions_1 = WPAuthSessionTokens::get_instance($this->default_user_id);

        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessionTokens::get_instance($this->other_user_id);

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
        $sessions_1 = WPAuthSessionTokens::get_instance($this->default_user_id);

        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessionTokens::get_instance($this->other_user_id);
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
        $sessions_1 = WPAuthSessionTokens::get_instance($this->default_user_id);

        $token = $sessions_1->create(time() + 10);
        $token_deleted = $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessionTokens::get_instance($this->other_user_id);
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
        $sessions_1 = WPAuthSessionTokens::get_instance($this->default_user_id);

        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);
        $sessions_1->create(time() + 10);

        $sessions_2 = WPAuthSessionTokens::get_instance($this->other_user_id);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);
        $sessions_2->create(time() + 10);

        WPAuthSessionTokens::drop_sessions();

        $this->assertCount(0, $sessions_1->get_all());
        $this->assertCount(0, $sessions_2->get_all());
    }

    /**
     * @test
     */
    public function that_expired_sessions_are_not_included(): void
    {
        $sessions_1 = WPAuthSessionTokens::get_instance(1);

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
        $sessions_1 = WPAuthSessionTokens::get_instance(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('expiration');

        $sessions_1->update((string) hash('sha256', 'foo'), []);
    }
}
