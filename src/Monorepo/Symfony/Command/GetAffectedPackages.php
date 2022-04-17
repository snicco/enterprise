<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use const JSON_PRETTY_PRINT;

final class GetAffectedPackages extends Command
{
    private PackageRepository $package_repo;

    public function __construct(PackageRepository $package_repo)
    {
        parent::__construct('affected-packages');
        $this->package_repo = $package_repo;
        $this->addArgument(
            'files',
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Space seperated list of modified/deleted files'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = (array) $input->getArgument('files');

        $output->writeln($this->package_repo->getAffected($files)->toJson(fn (Package $package): array => [
            'abs_path' => $package->absolute_directory_path,
            'name' => $package->name,
            'vendor_name' => $package->vendor_name,
            'short_name' => $package->short_name,
        ], JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
