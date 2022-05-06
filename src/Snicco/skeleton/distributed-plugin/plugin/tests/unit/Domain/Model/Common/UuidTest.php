<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\unit\Domain\Model\Common;

use Codeception\Test\Unit;
use InvalidArgumentException;
use VENDOR_NAMESPACE\Domain\Model\Common\Uuid;

/**
 * @internal
 */
final class UuidTest extends Unit
{
    /**
     * @test
     * @dataProvider invalidUuids
     */
    public function that_invalid_uuids_throw_an_exception(string $uuid): void
    {
        $this->expectException(InvalidArgumentException::class);
        UuidValueObject::fromString($uuid);
    }

    /**
     * @test
     */
    public function that_valid_uuids_can_be_created(): void
    {
        $id = UuidValueObject::fromString('0ffad2c2-93aa-4cea-aa79-923acc8ef802');
        $this->assertSame('0ffad2c2-93aa-4cea-aa79-923acc8ef802', $id->asString());
    }

    public function invalidUuids(): array
    {
        return [
            ['foo'],
            ['0ffad2c2-93aa-4cea-aa79-923acc8ef802-bogus'],
            ['bogus-0ffad2c2-93aa-4cea-aa79-923acc8ef802'],
            ['1234'],
        ];
    }
}

final class UuidValueObject
{
    use Uuid;
}
