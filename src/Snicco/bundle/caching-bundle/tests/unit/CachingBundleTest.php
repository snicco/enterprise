<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Caching\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Caching\CachingBundle;

final class CachingBundleTest extends Unit
{
    
    /**
     * @test
     */
    public function that_the_test_method_works() :void
    {
        $this->assertSame('foo', CachingBundle::testMethod());
    }
    
}