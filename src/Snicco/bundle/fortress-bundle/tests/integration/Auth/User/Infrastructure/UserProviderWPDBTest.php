<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration\Auth\User\Infrastructure;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Domain\UserNotFound;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Infrastructure\UserProviderWPDB;
use WP_User;

/**
 * @internal
 */
final class UserProviderWPDBTest extends WPTestCase
{
    /**
     * @test
     */
    public function that_a_user_can_be_found_by_email(): void
    {
        $default_admin = new WP_User(1);

        $provider = new UserProviderWPDB();

        $user = $provider->getUserByIdentifier($default_admin->user_email);

        $this->assertEquals($default_admin, $user);
    }

    /**
     * @test
     */
    public function that_a_user_can_be_found_by_login_name(): void
    {
        $default_admin = new WP_User(1);

        $provider = new UserProviderWPDB();

        $user = $provider->getUserByIdentifier($default_admin->user_login);

        $this->assertEquals($default_admin, $user);
    }

    /**
     * @test
     */
    public function that_a_user_can_be_found_by_id(): void
    {
        $default_admin = new WP_User(1);

        $provider = new UserProviderWPDB();

        $user = $provider->getUserByIdentifier('1');

        $this->assertEquals($default_admin, $user);
    }

    /**
     * @test
     */
    public function that_an_id_of_null_throws_an_exception(): void
    {
        $provider = new UserProviderWPDB();

        $this->expectException(UserNotFound::class);

        $provider->getUserByIdentifier('0');
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_no_user_can_be_found(): void
    {
        $provider = new UserProviderWPDB();

        $this->expectException(UserNotFound::class);

        $provider->getUserByIdentifier('bogus_1234');
    }

    /**
     * @test
     */
    public function that_user_existence_can_be_checked(): void
    {
        $default_admin = new WP_User(1);

        $provider = new UserProviderWPDB();

        $this->assertTrue($provider->exists((string) $default_admin->ID));
        $this->assertTrue($provider->exists($default_admin->user_login));
        $this->assertTrue($provider->exists($default_admin->user_email));

        // @todo There is a shared fixture conflict somewhere here.
        $this->assertFalse($provider->exists('1232143234324'));
        $this->assertFalse($provider->exists('bogus'));
        $this->assertFalse($provider->exists('bogus@web.de'));
    }
}
