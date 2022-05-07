<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\HTTP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\HTTP\RequestMethod;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

/**
 * @internal
 */
final class RequestMethodTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new RequestMethod('GET');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_METHOD' => 'GET',
        ])));

        $condition = new RequestMethod(['GET', 'OPTIONS']);
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_METHOD' => 'GET',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new RequestMethod('GET');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_METHOD' => 'POST',
        ])));

        $condition = new RequestMethod(['POST', 'PATCH']);
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_METHOD' => 'GET',
        ])));
    }
}
