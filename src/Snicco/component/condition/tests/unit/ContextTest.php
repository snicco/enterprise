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
}
