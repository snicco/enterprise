<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Shared\Infrastructure;

use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;

final class FortressRouteLoadingOptions implements RouteLoadingOptions
{
    /**
     * @var string
     */
    public const ROUTE_FILE_IDENTIFIER = 'fortress';

    private RouteLoadingOptions $default_options;

    private string $auth_prefix;

    public function __construct(RouteLoadingOptions $default_options, string $auth_prefix)
    {
        $this->auth_prefix = $auth_prefix;
        $this->default_options = $default_options;
    }

    public function getApiRouteAttributes(string $file_basename, ?string $parsed_version): array
    {
        if (self::ROUTE_FILE_IDENTIFIER !== $file_basename) {
            return $this->default_options->getApiRouteAttributes(
                $file_basename,
                $parsed_version
            );
        }

        return [
            RoutingConfigurator::PREFIX_KEY => $this->auth_prefix . '/api',
            RoutingConfigurator::NAME_KEY => 'fortress',
        ];
    }

    public function getRouteAttributes(string $file_basename): array
    {
        if (self::ROUTE_FILE_IDENTIFIER !== $file_basename) {
            return $this->default_options->getRouteAttributes(
                $file_basename,
            );
        }

        return [
            RoutingConfigurator::PREFIX_KEY => $this->auth_prefix,
            RoutingConfigurator::NAME_KEY => self::ROUTE_FILE_IDENTIFIER,
        ];
    }
}
