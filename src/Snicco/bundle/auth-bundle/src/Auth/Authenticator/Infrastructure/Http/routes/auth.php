<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return static function (WebRoutingConfigurator $router) {
    
    $router->name('snicco_auth.2fa')
           ->prefix('two-factor')
           ->group(function (WebRoutingConfigurator $router) {
               $router->get('challenge', '/challenge/{token}');
           });
    
};