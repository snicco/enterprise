<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use const JSON_PRETTY_PRINT;

final class GetAllPackages extends Command
{
    private PackageRepository $package_repo;
    private RepositoryRoot $repository_root;
    
    public function __construct(PackageRepository $package_repo, RepositoryRoot $repository_root)
    {
        parent::__construct('get-packages');
        $this->package_repo = $package_repo;
        $this->repository_root = $repository_root;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->package_repo->getAll()->toJson(fn (Package $package): array => [
            'abs_path' => $package->absolute_directory_path,
            'rel_path' => Str::replaceFirst($package->absolute_directory_path, (string) $this->repository_root, ''),
            'name' => $package->name,
            'vendor_name' => $package->vendor_name,
            'short_name' => $package->short_name,
        ], JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
