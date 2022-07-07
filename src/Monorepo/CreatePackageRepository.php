<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo;

use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;

final class CreatePackageRepository
{
    public static function fromRepoRoot(RepositoryRoot $repository_root): PackageRepository
    {
        return new PackageRepository([
            $repository_root->append('/src/Snicco/bundle'),
            $repository_root->append('/src/Snicco/component'),
            $repository_root->append('/src/Snicco/plugin'),
        ], $repository_root);
    }
}
