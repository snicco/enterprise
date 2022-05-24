<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\wpunit\Auth\User;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\AuthBundle\Auth\User\Domain\UserNotFound;
use Snicco\Enterprise\AuthBundle\Auth\User\WPUserProvider;
use WP_User;

/**
 * @internal
 */
final class WPUserProviderTest extends WPTestCase
{
    /**
     * @test
     */
    public function that_a_user_can_be_found_by_email(): void
    {
        $default_admin = new WP_User(1);

        $provider = new WPUserProvider();

        $user = $provider->getUserByIdentifier($default_admin->user_email);

        $this->assertEquals($default_admin, $user);
    }

    /**
     * @test
     */
    public function that_a_user_can_be_found_by_login_name(): void
    {
        $default_admin = new WP_User(1);

        $provider = new WPUserProvider();

        $user = $provider->getUserByIdentifier($default_admin->user_login);

        $this->assertEquals($default_admin, $user);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_no_user_can_be_found(): void
    {
        $provider = new WPUserProvider();

        $this->expectException(UserNotFound::class);

        $provider->getUserByIdentifier('bogus_1234');
    }
}
