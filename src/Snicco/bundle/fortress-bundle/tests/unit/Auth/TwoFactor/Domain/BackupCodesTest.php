<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\unit\Auth\TwoFactor\Domain;

use Codeception\Test\Unit;
use InvalidArgumentException;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\BackupCodes;

use function iterator_to_array;
use function sprintf;
use function str_repeat;

/**
 * @internal
 */
final class BackupCodesTest extends Unit
{
    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_a_plain_code_is_not_17_chars_long(): void
    {
        $codes = BackupCodes::generate();
        $str = str_repeat('x', 18);
        $codes[] = $str;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid key [%s]', $str));
        BackupCodes::fromPlainCodes($codes);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_a_plain_code_is_not_correct_format(): void
    {
        $codes = BackupCodes::generate();
        $codes[] = str_repeat('x', 8) . ':' . str_repeat('x', 8);
        $str = (str_repeat('x', 8) . ':' . str_repeat('x', 8));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid key [%s]', $str));
        BackupCodes::fromPlainCodes($codes);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_invalid_hashes(): void
    {
        $codes = BackupCodes::fromPlainCodes();

        $codes_array = iterator_to_array($codes);
        $codes_array[] = 'foo';

        $this->expectException(InvalidArgumentException::class);

        BackupCodes::fromHashedCodes($codes_array);
    }
}
