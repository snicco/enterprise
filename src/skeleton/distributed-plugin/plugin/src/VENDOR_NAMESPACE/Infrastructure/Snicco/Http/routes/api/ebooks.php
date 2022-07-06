<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller\EbookAPIController;

return function (WebRoutingConfigurator $router): void {
    // This route will be available at /VENDOR_SLUG/api/all (configured in routing.php config)
    $router->get('all', '/all', [EbookAPIController::class, 'listForCustomers']);
};
