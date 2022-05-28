<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Http\Controller\TwoFactorChallengeController;
use Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure\AcceptsJsonOnly;
use Webmozart\Assert\Assert;

return static function (WebRoutingConfigurator $router): void {
    
    $wp = new Snicco\Component\BetterWPAPI\BetterWPAPI();
    
    $two_factor_path_prefix = $wp->applyFiltersStrict('snicco/fortress-bundle:two_factor_prefix', '/two-factor');
    $two_factor_challenge_path = $wp->applyFiltersStrict('snicco/fortress-bundle:two_factor_challenge_path', '/challenge');
    
    Assert::stringNotEmpty($two_factor_path_prefix);
    Assert::stringNotEmpty($two_factor_challenge_path);
    
    $router
        ->name('2fa')
        ->prefix($two_factor_path_prefix)
        ->middleware(AcceptsJsonOnly::class)
        ->group(function (WebRoutingConfigurator $router) use ($two_factor_challenge_path): void {
            $router->match(
                ['GET', 'POST'],
                'challenge',
                $two_factor_challenge_path,
                TwoFactorChallengeController::class
            );
        });
};