<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco;

use InvalidArgumentException;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Component\Kernel\Configuration\ConfigCache;
use Snicco\Component\Kernel\Configuration\PHPFileCache;
use Snicco\Component\StrArr\Arr;

use function get_home_url;
use function parse_url;

use const PHP_URL_HOST;

/**
 * In a distributed plugin we don't control the host where the site will be
 * deployed. It might even be a multi-site install. In order to generate correct
 * URLs we need to get the host at runtime a can't read it from cache.
 */
final class ConfigCacheWithRuntimeChecks implements ConfigCache
{
    public function get(string $key, callable $loader): array
    {
        $data = (new PHPFileCache())->get($key, $loader);

        $current_host = (string) parse_url(get_home_url(), PHP_URL_HOST);

        if (empty($current_host)) {
            throw new InvalidArgumentException(
                'VENDOR_TITLE: current value of get_home_url does not contain a valid host'
            );
        }

        Arr::set($data, 'routing.' . RoutingOption::HOST, $current_host);

        return $data;
    }
}
