<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\cli\Auth\TwoFactor;

use Snicco\Enterprise\Bundle\Fortress\Tests\WPCLITester;

use function putenv;

final class Initialize2FaCest
{
    public function _before() :void {
        putenv('COLUMNS=144');
    }
    
    public function that_two_factor_settings_can_not_be_created_for_an_invalid_user(WPCLITester $I) :void
    {
        $I->cli(['snicco/fortress 2fa:initialize', "2"]);
        
        $I->seeInShellOutput('user');
        $I->seeResultCodeIs(1);
    
        $I->cli(['snicco/fortress 2fa:initialize', 'bogus@web.de']);
    
        $I->seeInShellOutput('user');
        $I->seeResultCodeIs(1);
    
        $I->cli(['snicco/fortress 2fa:initialize', 'bogus_login']);
    
        $I->seeInShellOutput('user');
        $I->seeResultCodeIs(1);
    }
    
    /**
     * @test
     */
    public function that_two_factor_settings_can_be_created_by_user_id(WPCLITester $I) :void
    {
        $calvin_id = $I->haveUserInDatabase('calvin', 'admin', ['user_email' => 'calvin@example.org']);
    
        $I->cli(['snicco/fortress 2fa:initialize', "$calvin_id"]);
    
        $I->seeInShellOutput('Secret: ');
        $I->seeInShellOutput('Backup-Codes: ');
        $I->seeResultCodeIs(0);
    }
    
    /**
     * @test
     */
    public function that_two_factor_settings_can_be_created_by_user_login(WPCLITester $I) :void
    {
        $I->haveUserInDatabase('calvin', 'admin', ['user_email' => 'calvin@example.org']);
        
        $I->cli(['snicco/fortress 2fa:initialize', "calvin"]);
        
        $I->seeInShellOutput('Secret: ');
        $I->seeInShellOutput('Backup-Codes: ');
        $I->seeResultCodeIs(0);
    }
    
    /**
     * @test
     */
    public function that_two_factor_settings_can_be_created_by_user_email(WPCLITester $I) :void
    {
        $I->haveUserInDatabase('calvin', 'admin', ['user_email' => 'calvin@web.org']);
        
        $I->cli(['snicco/fortress 2fa:initialize', "calvin@web.org"]);
        
        $I->seeInShellOutput('Secret: ');
        $I->seeInShellOutput('Backup-Codes: ');
        $I->seeResultCodeIs(0);
    }
    
}