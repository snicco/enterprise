<?php

declare(strict_types=1);

use Snicco\Enterprise\Monorepo\CreatePackageRepository;
use Snicco\Enterprise\Monorepo\Symfony\Command\CreatePackageGitHubRepo;
use Snicco\Enterprise\Monorepo\Symfony\Command\GenerateCommitScopes;
use Snicco\Enterprise\Monorepo\Symfony\Command\GetAffectedPackages;
use Snicco\Enterprise\Monorepo\Symfony\Command\GetAllPackages;
use Snicco\Enterprise\Monorepo\Symfony\Command\SplitPackage;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;
use Symfony\Component\Console\Application;

try {
    require \dirname(__DIR__) . '/vendor/autoload.php';

    $repo_root = new RepositoryRoot(\dirname(__DIR__));
    $package_repo = CreatePackageRepository::fromRepoRoot($repo_root);

    $application = new Application();

    $application->add(new GenerateCommitScopes($package_repo, $repo_root));
    $application->add(new GetAllPackages($package_repo, $repo_root));
    $application->add(new CreatePackageGitHubRepo($package_repo));
    $application->add(new GetAffectedPackages($package_repo));
    $application->add(new SplitPackage($package_repo));

    exit($application->run());
} catch (Throwable $e) {
    $message = $e->getMessage();

    echo \PHP_EOL . \PHP_EOL . "\033[0;31m[ERROR] " . $message . "\033[0m" . \PHP_EOL . \PHP_EOL;

    exit(1);
}
