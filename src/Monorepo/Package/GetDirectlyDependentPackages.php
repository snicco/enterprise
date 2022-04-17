<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Package;

use function in_array;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Monorepo\Package
 */
final class GetDirectlyDependentPackages
{
    public function __invoke(PackageCollection $packages, PackageCollection $all_packages): PackageCollection
    {
        return $this->resolveDependents($packages, $all_packages);
    }

    private function resolveDependents(
        PackageCollection $packages,
        PackageCollection $all_packages
    ): PackageCollection {
        $direct_dependents = new PackageCollection([]);

        foreach ($packages as $package) {
            $direct_dependents = $direct_dependents->merge(
                $this->resolveDependentsForPackage($package->name, $all_packages)
            );
        }

        return $direct_dependents;
    }

    private function resolveDependentsForPackage(
        string $full_name,
        PackageCollection $all_packages
    ): PackageCollection {
        return $all_packages->filter(
            fn (Package $package): bool => in_array($full_name, $package->first_party_dependencies, true)
        );
    }
}
