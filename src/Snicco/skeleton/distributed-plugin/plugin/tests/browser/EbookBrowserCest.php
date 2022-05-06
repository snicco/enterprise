<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\browser;

use VENDOR_NAMESPACE\Tests\End2EndTester;

use function strtolower;

class EbookBrowserCest
{
    public function _before(End2EndTester $I): void
    {
        // Activate a plugin.
        $I->loginAsAdmin();
        $I->amOnPluginsPage();
        $I->activatePlugin(strtolower('VENDOR_TITLE') . '-plugin');
    }

    public function creating_an_ebook(End2EndTester $I): void
    {
        $I->amOnPage('/ebooks');
        $I->see('Available Ebooks (0)');

        $I->click('Create a new ebook');

        $I->submitForm('#create-ebook-form', [
            'title' => 'Ebook title (selenium)',
            'description' => 'Ebook description (selenium)',
            'price' => 2500,
        ]);

        $I->see('Ebook title (selenium)');
        $I->see('This ebooks will be available soon for $25.00');

        $I->amOnPage('/ebooks');
        $I->see('Available Ebooks (1)');
    }

    public function archiving_an_ebook(End2EndTester $I): void
    {
        $I->loginAsAdmin();
        $I->amOnPage('/ebooks');

        $I->click('Create a new ebook');

        $I->submitForm('#create-ebook-form', [
            'title' => 'Ebook title (selenium)',
            'description' => 'Ebook description (selenium)',
            'price' => 2500,
        ]);

        $I->amOnPage('/ebooks');
        $I->see('Ebook title (selenium)');

        $I->click('Details');
        $I->click('Archive this ebook');

        $I->seeCurrentUrlEquals('/ebooks');
        $I->dontSee('Ebook title (selenium)');
    }
}
