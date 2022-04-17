<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use const JSON_PRETTY_PRINT;

final class GetAllPackages extends Command
{
    private PackageRepository $package_repo;

    public function __construct(PackageRepository $package_repo)
    {
        parent::__construct('get-packages');
        $this->package_repo = $package_repo;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->package_repo->getAll()->toJson(fn (Package $package): array => [
            'abs_path' => $package->absolute_directory_path,
            'name' => $package->name,
            'vendor_name' => $package->vendor_name,
            'short_name' => $package->short_name,
        ], JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
