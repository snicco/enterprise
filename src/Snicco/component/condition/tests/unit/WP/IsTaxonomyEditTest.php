<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\WP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsTaxonomyEdit;

/**
 * @internal
 */
final class IsTaxonomyEditTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new IsTaxonomyEdit();
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/term.php',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-admin/term.php',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsTaxonomyEdit();
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-foo/term.php',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-foo/term.php',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsTaxonomyEdit());
    }
}
