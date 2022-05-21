<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Password;

use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use WP_Error;
use WP_User;

use function dirname;
use function do_action;
use function str_repeat;

/**
 * @internal
 */
final class PasswordPolicyPasswordResetTest extends WPTestCase
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
    public function that_passwords_are_checked_for_pass1_post_variable(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $user = new WP_User(1);

        $_POST['pass1'] = 'foobar';

        do_action('validate_password_reset', $errors, $user);

        $this->assertTrue($errors->has_errors(), 'Password policy was not enforced');

        $messages = $errors->get_error_messages('insufficient_pw_length');
        $this->assertSame([
            'Passwords must have at least 12 characters.',
        ], $messages);
    }

    /**
     * @test
     */
    public function that_passwords_are_checked_for_password1_post_variable(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $user = new WP_User(1);

        $_POST['password1'] = 'foobar';

        do_action('validate_password_reset', $errors, $user);

        $this->assertTrue($errors->has_errors(), 'Password policy was not enforced');

        $messages = $errors->get_error_messages('insufficient_pw_length');
        $this->assertSame([
            'Passwords must have at least 12 characters.',
        ], $messages);
    }

    /**
     * @test
     */
    public function that_passwords_are_checked_for_password_1_post_variable(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $user = new WP_User(1);

        $_POST['password_1'] = 'foobar';

        do_action('validate_password_reset', $errors, $user);

        $this->assertTrue($errors->has_errors(), 'Password policy was not enforced');

        $messages = $errors->get_error_messages('insufficient_pw_length');
        $this->assertSame([
            'Passwords must have at least 12 characters.',
        ], $messages);
    }

    /**
     * @test
     */
    public function that_passwords_cant_be_too_long(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $user = new WP_User(1);

        $_POST['pass1'] = str_repeat('x', 5000);

        do_action('validate_password_reset', $errors, $user);

        $this->assertTrue($errors->has_errors(), 'Password policy was not enforced');

        $messages = $errors->get_error_messages('pw_length_exceeded');
        $this->assertSame([
            'Passwords must not have more than 4096 characters.',
        ], $messages);
    }

    /**
     * @test
     */
    public function that_password_must_have_enough_entropy(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $user = new WP_User(1);

        $_POST['pass1'] = str_repeat('a', 13);

        do_action('validate_password_reset', $errors, $user);

        $this->assertTrue($errors->has_errors(), 'Password policy was not enforced');

        $messages = $errors->get_error_messages('insufficient_pw_entropy');
        $this->assertSame([
            'Your password is to insecure or contains references to your personal information. Please generate a secure password.',
        ], $messages);
    }

    /**
     * @test
     */
    public function that_a_weak_password_can_be_used_for_skipped_roles(): void
    {
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('snicco_auth.password.password_policy_excluded_roles', ['administrator']);
        });
        $this->kernel->boot();

        $errors = new WP_Error();
        $user = new WP_User(1);

        $_POST['pass1'] = 'foobar';

        do_action('validate_password_reset', $errors, $user);

        $this->assertFalse($errors->has_errors(), 'Password policy was enforced for excluded role.');
    }

    /**
     * @test
     */
    public function that_a_valid_password_does_not_error(): void
    {
        $this->kernel->boot();

        $errors = new WP_Error();
        $user = new WP_User(1);

        $_POST['pass1'] = 'correct-horse-battery-staple';

        do_action('validate_password_reset', $errors, $user);

        $this->assertFalse($errors->has_errors(), 'A valid password errored.');
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__, 2) . '/fixtures';
    }
}
