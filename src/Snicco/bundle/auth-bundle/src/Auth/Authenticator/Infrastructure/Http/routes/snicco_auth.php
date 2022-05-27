<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Infrastructure\Http\Controller\TwoFactorChallengeController;

return static function (WebRoutingConfigurator $router): void {
    $router
        ->name('2fa')
        ->prefix('two-factor')
        ->group(function (WebRoutingConfigurator $router): void {
            $router->match(['GET', 'POST'],'challenge', '/challenge', [TwoFactorChallengeController::class, 'challenge']);
        });
 
};
