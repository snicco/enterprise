<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\browser;

use Snicco\Enterprise\Bundle\Fortress\Tests\End2EndTester;

final class ExampleCest
{
    /**
     * @test
     */
    public function that_the_homepage_is_visible(End2EndTester $I): void
    {
        $I->amOnPage('/');
        $I->click('Hello world');

        $I->makeScreenshot('latest');
        $I->makeHtmlSnapshot('latest-html');

        $I->seeCurrentUrlEquals('/hello-world');
    }
}