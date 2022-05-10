<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\WP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsTaxonomyListEdit;

/**
 * @internal
 */
final class IsTaxonomyListEditTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new IsTaxonomyListEdit();
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/edit-tags.php',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-admin/edit-tags.php',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsTaxonomyListEdit();
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-foo/edit-tags.php',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-foo/edit-tags.php',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsTaxonomyListEdit());
    }
}
