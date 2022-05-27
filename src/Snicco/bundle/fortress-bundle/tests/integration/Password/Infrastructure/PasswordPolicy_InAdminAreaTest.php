<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration\Password\Infrastructure;

use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;
use WP_Error;
use WP_User;

use function dirname;
use function do_action;
use function str_repeat;

/**
 * @internal
 */
final class PasswordPolicy_InAdminAreaTest extends WPTestCase
{
    use BundleTestHelpers;

    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        $this->kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_POST = [];
    }

    /**
     * @test
     */
    public function that_a_password_can_not_be_updated_if_to_short(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'foobar';
        $_POST['pass2'] = 'foobar';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_length');

        $this->assertSame(['Passwords must have at least 12 characters.'], $error_messages);
    }

    /**
     * @test
     */
    public function that_a_password_can_not_be_updated_if_too_long(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = str_repeat('x', 5000);
        $_POST['pass2'] = str_repeat('x', 5000);

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('pw_length_exceeded');

        $this->assertSame(['Passwords must not have more than 4096 characters.'], $error_messages);
    }

    /**
     * @test
     */
    public function that_a_password_can_not_be_updated_if_entropy_is_to_small(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = str_repeat('x', 6) . str_repeat('y', 7);
        $_POST['pass2'] = str_repeat('x', 6) . str_repeat('y', 7);

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_entropy');

        $this->assertSame(
            [
                'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
            ],
            $error_messages
        );
    }

    /**
     * @test
     */
    public function that_the_blog_name_is_taken_into_account(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'Test Blog 1234';
        $_POST['pass2'] = 'Test Blog 1234';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_entropy');

        $this->assertSame(
            [
                'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
            ],
            $error_messages
        );
    }

    /**
     * @test
     */
    public function that_the_user_first_name_is_used_as_context(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'Foo Bar 1234';
        $_POST['pass2'] = 'Foo Bar 1234';

        $_POST['first_name'] = 'Foo Bar';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_entropy');

        $this->assertSame(
            [
                'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
            ],
            $error_messages
        );
    }

    /**
     * @test
     */
    public function that_the_user_last_name_is_used_as_context(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'Foo Bar 1234';
        $_POST['pass2'] = 'Foo Bar 1234';

        $_POST['last_name'] = 'Foo Bar';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_entropy');

        $this->assertSame(
            [
                'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
            ],
            $error_messages
        );
    }

    /**
     * @test
     */
    public function that_the_user_display_name_is_used_as_context(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'Foo Bar 1234';
        $_POST['pass2'] = 'Foo Bar 1234';

        $_POST['display_name'] = 'Foo Bar';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_entropy');

        $this->assertSame(
            [
                'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
            ],
            $error_messages
        );
    }

    /**
     * @test
     */
    public function that_the_user_nickname_is_used_as_context(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'Foo Bar 1234';
        $_POST['pass2'] = 'Foo Bar 1234';

        $_POST['nickname'] = 'Foo Bar';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_entropy');

        $this->assertSame(
            [
                'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
            ],
            $error_messages
        );
    }

    /**
     * @test
     */
    public function that_the_user_email_is_used_as_context(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'Foo Bar 1234';
        $_POST['pass2'] = 'Foo Bar 1234';

        $_POST['email'] = 'Foo Bar';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertTrue($errors->has_errors());

        $error_messages = $errors->get_error_messages('insufficient_pw_entropy');

        $this->assertSame(
            [
                'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
            ],
            $error_messages
        );
    }

    /**
     * @test
     */
    public function that_a_valid_password_can_be_set(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = (object) (array) new WP_User(1);

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'correct-horse-battery-staple';
        $_POST['pass2'] = 'correct-horse-battery-staple';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertFalse($errors->has_errors());
    }

    /**
     * @test
     */
    public function that_users_roles_can_be_excluded_from_the_policy(): void
    {
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('snicco_auth.password.password_policy_excluded_roles', ['administrator']);
        });
        $this->kernel->boot();

        $errors = new WP_Error();
        $update = true;
        $user = new stdClass();
        $user->ID = 1;

        $this->assertFalse($errors->has_errors());

        $_POST['pass1'] = 'foo';
        $_POST['pass2'] = 'foo';

        do_action('user_profile_update_errors', $errors, $update, $user);

        $this->assertFalse($errors->has_errors());
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__, 3) . '/fixtures/test-app';
    }
}
