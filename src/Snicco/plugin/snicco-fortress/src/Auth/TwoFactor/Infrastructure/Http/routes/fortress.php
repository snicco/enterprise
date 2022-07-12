<?php

declare(strict_types=1);

use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\Http\Controller\TwoFactorChallengeController;
use Snicco\Enterprise\Fortress\Shared\Infrastructure\AcceptsJsonOnly;
use Snicco\Middleware\WPGuestsOnly\WPGuestsOnly;
use Webmozart\Assert\Assert;

return static function (WebRoutingConfigurator $router): void {
    $wp = new BetterWPAPI();

    $two_factor_path_prefix = $wp->applyFiltersStrict('snicco/fortress-bundle:two_factor_prefix', '/two-factor');
    $two_factor_challenge_path = $wp->applyFiltersStrict(
        'snicco/fortress-bundle:two_factor_challenge_path',
        '/challenge'
    );

    Assert::stringNotEmpty($two_factor_path_prefix);
    Assert::stringNotEmpty($two_factor_challenge_path);

    $router
        ->name('2fa')
        ->prefix($two_factor_path_prefix)
        ->middleware([AcceptsJsonOnly::class, WPGuestsOnly::class])
        ->group(function (WebRoutingConfigurator $router) use ($two_factor_challenge_path): void {
            $router->match(
                ['GET', 'POST'],
                'challenge',
                $two_factor_challenge_path,
                TwoFactorChallengeController::class
            );
        });
};
