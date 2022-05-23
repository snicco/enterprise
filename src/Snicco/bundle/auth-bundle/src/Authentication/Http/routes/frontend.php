<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Enterprise\Bundle\Auth\Authentication\Http\Controller\TwoFactorController;

return function (WebRoutingConfigurator $router) {
  
  $router->get(
      'snicco.2fa.challenge',
      '/auth/two-factor-challenge/{user_id}',
      [TwoFactorController::class, 'challenge']
  )->requireNum('user_id');
  
};