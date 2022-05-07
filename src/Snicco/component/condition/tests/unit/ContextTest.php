<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Context;

use function rawurlencode;

/**
 * @internal
 */
final class ContextTest extends Unit
{
    /**
     * @test
     */
    public function that_the_path_is_url_decoded(): void
    {
        $city1 = rawurlencode('münchen');
        $city2 = rawurlencode('düsseldorf');
        $slash = rawurlencode('/');

        $context = new Context(
            [
                'REQUEST_URI' => '/' . $city1 . '/' . $slash . '/' . $city2,
            ],
            [],
            [],
            []
        );

        $this->assertSame('/münchen/%2F/düsseldorf', $context->path());
    }

    /**
     * @test
     */
    public function that_the_path_does_not_include_a_query_string(): void
    {
        $context = new Context(
            [
                'REQUEST_URI' => '/foo?bar=baz',
            ],
            [],
            [],
            []
        );

        $this->assertSame('/foo', $context->path());
    }

    /**
     * @test
     */
    public function that_the_path_returns_an_empty_string_if_not_set(): void
    {
        $context = new Context([], [], [], []);

        $this->assertSame('', $context->path());
    }
}
