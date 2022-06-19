<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\cli\Auth\TwoFactor;

use PragmaRX\Google2FA\Google2FA;
use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Bundle\Fortress\Tests\WPCLITester;
use Webmozart\Assert\Assert;

use function putenv;
use function sprintf;
use function trim;

final class Complete2FaCest
{
    public function _before(): void
    {
        putenv('COLUMNS=144');
    }

    /**
     * @test
     */
    public function that_the_two_factor_setup_can_be_completed_with_a_valid_otp(WPCLITester $I): void
    {
        $user = $I->haveUserInDatabase('calvin');

        $I->cli(['snicco/fortress 2fa:initialize', sprintf('%d', $user)]);

        $output = $I->grabLastShellOutput();

        Assert::startsWith($output, 'Secret: ');
        $secret = trim(Str::betweenFirst($output, 'Secret: ', "\n"), );
        Assert::stringNotEmpty($secret, '2FA Secret not found in shell output');

        $google_fa = new Google2FA();
        $valid_otp = $google_fa->getCurrentOtp($secret);

        $I->cli(['snicco/fortress 2fa:complete', sprintf('%d', $user), $valid_otp]);
        $I->seeResultCodeIs(0);

        $I->canSeeInDatabase('wp_snicco_fortress_2fa_settings', [
            'user_id' => $user,
            'completed' => 1,
        ]);
    }

    /**
     * @test
     */
    public function that_the_two_factor_setup_cant_be_completed_with_an_invalid_otp(WPCLITester $I): void
    {
        $user = $I->haveUserInDatabase('calvin');

        $I->cli(['snicco/fortress 2fa:initialize', sprintf('%d', $user)]);

        $output = $I->grabLastShellOutput();
        $secret = trim(Str::betweenFirst($output, 'Secret: ', "\n"));
        Assert::stringNotEmpty($secret);

        $google_fa = new Google2FA();
        $valid_otp = $google_fa->getCurrentOtp($secret);

        $invalid = '123456';
        Assert::notSame($invalid, $valid_otp);

        $I->cli(['snicco/fortress 2fa:complete', sprintf('%d', $user), $invalid]);
        $I->seeResultCodeIs(1);
        $I->seeInShellOutput('Invalid OTP');

        $I->cantSeeInDatabase('wp_snicco_fortress_2fa_settings', [
            'user_id' => $user,
            'completed' => 1,
        ]);
    }
}
