<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\fixtures;

use BadMethodCallException;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

use function rtrim;
use function sprintf;

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

        $route = $this->routes[$name];

        if (empty($arguments)) {
            return $route;
        }

        $route .= '?';

        foreach ($arguments as $key => $value) {
            $route .= sprintf('%s=%s&', $key, $value);
        }

        return rtrim($route, '&');
    }

    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH): string
    {
        throw new BadMethodCallException(__METHOD__);
    }
}
