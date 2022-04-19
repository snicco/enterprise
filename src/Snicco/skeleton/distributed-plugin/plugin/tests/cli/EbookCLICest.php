<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\cli;

use VENDOR_NAMESPACE\Tests\CLITester;

class EbookCLICest
{
    public function _before(CLITester $I): void
    {
        $I->cli(['plugin', 'activate', 'plugin/PLUGIN_BASENAME.php']);
        $I->seeResultCodeIs(0);
    }

    public function creating_an_ebook_via_cli(CLITester $I): void
    {
        $I->cli(['VENDOR_SLUG', 'ebook', 'create', '"My Ebook Title"', '2500', '"My Ebook description"']);
        $I->seeResultCodeIs(0);

        $I->cli(['VENDOR_SLUG', 'ebook', 'list']);

        $I->seeInShellOutput('My Ebook Title');
        $I->seeInShellOutput('My Ebook description');
    }

    public function archiving_an_ebook(CLITester $I): void
    {
        $I->cli(['VENDOR_SLUG', 'ebook', 'create', '"My Ebook Title"', '2500', '"My Ebook description"']);

        $id = $I->grabLastShellOutput();

        $I->cli(['VENDOR_SLUG', 'ebook', 'list']);

        $I->seeInShellOutput('My Ebook Title');
        $I->seeInShellOutput('My Ebook description');

        $I->cli(['VENDOR_SLUG', 'ebook', 'archive', $id]);

        $I->dontSeeInShellOutput('My Ebook Title');
        $I->dontSeeInShellOutput('My Ebook description');
    }

    public function archiving_all_ebooks(CLITester $I): void
    {
        $I->cli(['VENDOR_SLUG', 'ebook', 'create', '"Ebook 1 title"', '2500', '"Ebook 1 description"']);
        $I->cli(['VENDOR_SLUG', 'ebook', 'create', '"Ebook 2 title"', '2500', '"Ebook 2 description"']);

        $I->cli(['VENDOR_SLUG', 'ebook', 'list']);

        $I->seeInShellOutput('Ebook 1 title');
        $I->seeInShellOutput('Ebook 2 title');

        $I->cli(['VENDOR_SLUG', 'ebook', 'archive:all', '--no-interaction']);

        $I->cli(['VENDOR_SLUG', 'ebook', 'list']);

        $I->dontSeeInShellOutput('Ebook 1 title');
        $I->dontSeeInShellOutput('Ebook 2 title');
    }
}
