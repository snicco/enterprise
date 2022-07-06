<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller\EbookController;

return function (WebRoutingConfigurator $configurator): void {
    $configurator->get('ebook.index', '/ebooks', [EbookController::class, 'listForCustomers']);

    $configurator->get('ebook.show', '/ebooks/{id}', [EbookController::class, 'show']);

    $configurator->match(['GET', 'POST'], 'ebook.create', '/ebook/create', [EbookController::class, 'create']);

    $configurator->patch('ebook.archive', '/ebooks/{id}/archive', [EbookController::class, 'archive']);
};
