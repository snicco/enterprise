<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\fixtures;

use BadMethodCallException;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

final class StubUrlGenerator implements UrlGenerator
{
    /**
     * @var array<string,string>
     */
    private array $routes;

    /**
     * @param array<string,string> $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function to(
        string $path,
        array $extra = [],
        int $type = self::ABSOLUTE_PATH,
        ?bool $https = null
    ): string {
        throw new BadMethodCallException(__METHOD__);
    }

    public function toRoute(
        string $name,
        array $arguments = [],
        int $type = self::ABSOLUTE_PATH,
        ?bool $https = null
    ): string {
        if (! isset($this->routes[$name])) {
            throw RouteNotFound::name($name);
        }

        return $this->routes[$name];
    }

    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH): string
    {
        throw new BadMethodCallException(__METHOD__);
    }
}
