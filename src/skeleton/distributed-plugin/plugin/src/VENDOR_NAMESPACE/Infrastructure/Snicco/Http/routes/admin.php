<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller\AdminPageController;

return function (AdminRoutingConfigurator $router) {
    $parent = $router->page(
        'VENDOR_SLUG.main',
        '/admin.php/VENDOR_SLUG',
        AdminPageController::class,
        [
            AdminMenuItem::CAPABILITY => 'manage_options',
            AdminMenuItem::ICON => 'dashicons-admin-site',
            // Dont use __() here. This function call is cached in production env.
            AdminMenuItem::PAGE_TITLE => 'admin.main.page_title',
            AdminMenuItem::MENU_TITLE => 'admin.main.menu_title',
        ]
    );

    $router->page(
        'VENDOR_SLUG.support',
        '/admin.php/VENDOR_SLUG-support',
        [AdminPageController::class, 'support'],
        [
            AdminMenuItem::CAPABILITY => 'manage_options',
            // Dont use __() here. This function call is cached in production env.
            AdminMenuItem::PAGE_TITLE => 'admin.support.page_title',
            AdminMenuItem::MENU_TITLE => 'admin.support.menu_title',
        ],
        $parent
    );
};
