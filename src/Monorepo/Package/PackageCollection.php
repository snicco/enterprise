<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Package;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function json_encode;
use function ksort;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-immutable
 *
 * @template-implements IteratorAggregate<int,Package>
 */
final class PackageCollection implements Countable, IteratorAggregate
{
    /**
     * @var Package[]
     */
    private array $packages = [];

    /**
     * @param Package[] $packages
     */
    public function __construct(array $packages = [])
    {
        foreach ($packages as $package) {
            $id = $package->name;
            if (isset($this->packages[$id])) {
                continue;
            }

            $this->packages[$id] = $package;
        }

        ksort($this->packages);
    }

    public function count(): int
    {
        return count($this->packages);
    }

    public function merge(PackageCollection $collection): PackageCollection
    {
        return new self(array_merge($this->packages, $collection->packages));
    }

    /**
     * @param callable(Package):bool $condition
     *
     * @psalm-param pure-callable(Package):bool $condition
     */
    public function filter(callable $condition): PackageCollection
    {
        $packages = array_filter($this->packages, $condition);

        return new self($packages);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->packages));
    }

    /**
     * @psalm-param pure-callable(Package):array<string,mixed> $condition
     */
    public function toJson(callable $format, int $options = 0): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        $arr = array_values(array_map($format, $this->packages));

        return (string) json_encode($arr, $options | JSON_THROW_ON_ERROR);
    }
}
