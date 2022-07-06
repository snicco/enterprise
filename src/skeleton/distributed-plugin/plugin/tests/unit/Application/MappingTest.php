<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\unit\Application;

use Codeception\Test\Unit;
use VENDOR_NAMESPACE\Application\Mapping;

/**
 * @internal
 */
final class MappingTest extends Unit
{
    /**
     * @test
     */
    public function as_string(): void
    {
        $data = [
            'foo' => 'bar',
            'baz' => 1,
            'biz' => '',
        ];

        $this->assertSame('bar', Mapping::asString($data, 'foo'));
        $this->assertSame('1', Mapping::asString($data, 'baz'));
        $this->assertSame('', Mapping::asString($data, 'biz'));
        $this->assertSame('', Mapping::asString($data, 'boo'));
    }

    /**
     * @test
     */
    public function as_string_or_null(): void
    {
        $data = [
            'foo' => 'bar',
            'baz' => 1,
            'biz' => '',
        ];

        $this->assertSame('bar', Mapping::asStringOrNull($data, 'foo'));
        $this->assertSame('1', Mapping::asStringOrNull($data, 'baz'));
        $this->assertNull(Mapping::asStringOrNull($data, 'biz'));
        $this->assertNull(Mapping::asStringOrNull($data, 'boo'));
    }

    /**
     * @test
     */
    public function as_int(): void
    {
        $data = [
            'foo' => 1,
            'baz' => '1',
            'biz' => '',
            'bam' => 'string',
        ];

        $this->assertSame(1, Mapping::asInt($data, 'foo'));
        $this->assertSame(1, Mapping::asInt($data, 'baz'));
        $this->assertSame(0, Mapping::asInt($data, 'biz'));
        $this->assertSame(0, Mapping::asInt($data, 'boo'));
        $this->assertSame(0, Mapping::asInt($data, 'bam'));
    }

    /**
     * @test
     */
    public function as_int_or_null(): void
    {
        $data = [
            'foo' => 1,
            'baz' => '1',
            'biz' => '',
        ];

        $this->assertSame(1, Mapping::asIntOrNull($data, 'foo'));
        $this->assertSame(1, Mapping::asIntOrNull($data, 'baz'));
        $this->assertNull(Mapping::asIntOrNull($data, 'biz'));
        $this->assertNull(Mapping::asIntOrNull($data, 'boo'));
    }

    /**
     * @test
     */
    public function as_bool(): void
    {
        $data = [
            'foo' => true,
            'baz' => 1,
            'biz' => '',
            'string' => 'yes',
            'false' => false,
        ];

        $this->assertTrue(Mapping::asBool($data, 'foo'));
        $this->assertTrue(Mapping::asBool($data, 'baz'));
        $this->assertTrue(Mapping::asBool($data, 'string'));
        $this->assertFalse(Mapping::asBool($data, 'false'));
        $this->assertFalse(Mapping::asBool($data, 'biz'));
        $this->assertFalse(Mapping::asBool($data, 'boo'));
    }

    /**
     * @test
     */
    public function as_bool_or_null(): void
    {
        $data = [
            'foo' => true,
            'baz' => 1,
            'biz' => '',
            'string' => 'yes',
            'false' => false,
        ];

        $this->assertTrue(Mapping::asBool($data, 'foo'));
        $this->assertTrue(Mapping::asBool($data, 'baz'));
        $this->assertTrue(Mapping::asBool($data, 'string'));
        $this->assertFalse(Mapping::asBool($data, 'false'));
        $this->assertNull(Mapping::asBoolOrNull($data, 'biz'));
        $this->assertNull(Mapping::asBoolOrNull($data, 'boo'));
    }
}
