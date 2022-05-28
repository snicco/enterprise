<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\cli\Auth\TwoFactor;

use Snicco\Enterprise\Bundle\Fortress\Tests\WPCLITester;

use function putenv;

final class Delete2FaCest
{
    public function _before() :void
    {
        putenv('COLUMNS=144');
    }
    
    public function that_two_factor_settings_can_be_deleted_by_user_id(WPCLITester $I) :void
    {
        $user = $I->haveUserInDatabase('foo_user');
        
        $I->cli(['snicco/fortress 2fa:initialize', "$user"]);
        $I->seeResultCodeIs(0);
        
        $I->canSeeInDatabase('wp_snicco_fortress_2fa_settings', ['user_id' => $user]);
        
        $I->cli(['snicco/fortress 2fa:delete', "$user"]);
        $I->seeResultCodeIs(0);
        
        $I->cantSeeInDatabase('wp_snicco_fortress_2fa_settings', ['user_id' => $user]);
    }
    
    public function that_two_factor_settings_can_be_deleted_by_user_login(WPCLITester $I) :void
    {
        $id = $I->haveUserInDatabase('bar_user');
        
        $I->cli(['snicco/fortress 2fa:initialize', "bar_user"]);
        $I->seeResultCodeIs(0);
        
        $I->canSeeInDatabase('wp_snicco_fortress_2fa_settings', ['user_id' => $id]);
        
        $I->cli(['snicco/fortress 2fa:delete', "bar_user"]);
        $I->seeResultCodeIs(0);
        
        $I->cantSeeInDatabase('wp_snicco_fortress_2fa_settings', ['user_id' => $id]);
    }
    
    public function that_two_factor_settings_can_be_deleted_by_user_email(WPCLITester $I) :void
    {
        $id = $I->haveUserInDatabase('baz_user', 'admin', ['user_email' => 'baz@web.de']);
        
        $I->cli(['snicco/fortress 2fa:initialize', "baz@web.de"]);
        $I->seeResultCodeIs(0);
        
        $I->canSeeInDatabase('wp_snicco_fortress_2fa_settings', ['user_id' => $id]);
        
        $I->cli(['snicco/fortress 2fa:delete', "baz@web.de"]);
        $I->seeResultCodeIs(0);
        
        $I->cantSeeInDatabase('wp_snicco_fortress_2fa_settings', ['user_id' => $id]);
    }
    
    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_missing_users(WPCLITester $I) :void
    {
        $I->cli(['snicco/fortress 2fa:delete', '12344235']);
        $I->seeResultCodeIs(1);
        $I->seeInShellOutput('user');
    }
    
    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_users_without_2fa_enabled(WPCLITester $I) :void
    {
        $user = $I->haveUserInDatabase('foobaz');
        
        $I->cli(['snicco/fortress 2fa:delete', "$user"]);
        $I->seeResultCodeIs(1);
        $I->seeInShellOutput('No 2FA-settings');
    }
    
}