<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\unit\Password;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Fortress\Password\Domain\Exception\InsufficientPasswordEntropy;
use Snicco\Enterprise\Bundle\Fortress\Password\Domain\Exception\InsufficientPasswordLength;
use Snicco\Enterprise\Bundle\Fortress\Password\Domain\Exception\PasswordLengthExceeded;
use Snicco\Enterprise\Bundle\Fortress\Password\Domain\PasswordPolicy;

use function str_repeat;

/**
 * @internal
 */
final class PasswordPolicyTest extends Unit
{
    /**
     * @test
     */
    public function that_at_least_12_chars_are_required(): void
    {
        $password = 'XyasGasdwq3';

        $policy = new PasswordPolicy();

        $this->expectException(InsufficientPasswordLength::class);

        $policy->check($password);
    }

    /**
     * @test
     */
    public function that_at_max_4096_chars_are_allowed(): void
    {
        $password = str_repeat('x', 4096);

        $policy = new PasswordPolicy();

        $policy->check($password);

        $this->expectException(PasswordLengthExceeded::class);

        $policy->check($password . 'a');
    }

    /**
     * @test
     */
    public function that_at_least_a_3_score_is_needed_with_zxcvbn(): void
    {
        $policy = new PasswordPolicy();

        $this->expectException(InsufficientPasswordEntropy::class);
        $policy->check('password12345');
    }

    /**
     * @test
     */
    public function that_a_password_can_be_valid(): void
    {
        $this->expectNotToPerformAssertions();

        $policy = new PasswordPolicy();
        $policy->check('correct horse battery staple');

        $policy = new PasswordPolicy(100);
        $policy->check(str_repeat('x', 101));
    }
}
