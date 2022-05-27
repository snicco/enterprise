<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure;

use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;

final class AuthRouteLoadingOptions implements RouteLoadingOptions
{
    /**
     * @var string
     */
    public const AUTH_ROUTE_FILE_IDENTIFIER = 'snicco_auth';

    private RouteLoadingOptions $default_options;

    private string $auth_prefix;

    public function __construct(RouteLoadingOptions $default_options, string $auth_prefix)
    {
        $this->auth_prefix = $auth_prefix;
        $this->default_options = $default_options;
    }

    public function getApiRouteAttributes(string $file_basename, ?string $parsed_version): array
    {
        if (self::AUTH_ROUTE_FILE_IDENTIFIER !== $file_basename) {
            return $this->default_options->getApiRouteAttributes(
                $file_basename,
                $parsed_version
            );
        }

        return [
            RoutingConfigurator::PREFIX_KEY => $this->auth_prefix . '/api',
            RoutingConfigurator::NAME_KEY => 'snicco_auth',
        ];
    }

    public function getRouteAttributes(string $file_basename): array
    {
        if (self::AUTH_ROUTE_FILE_IDENTIFIER !== $file_basename) {
            return $this->default_options->getRouteAttributes(
                $file_basename,
            );
        }

        return [
            RoutingConfigurator::PREFIX_KEY => $this->auth_prefix,
            RoutingConfigurator::NAME_KEY => self::AUTH_ROUTE_FILE_IDENTIFIER,
        ];
    }
}
