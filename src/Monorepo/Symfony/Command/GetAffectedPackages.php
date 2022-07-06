<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption('pretty', 'p', InputOption::VALUE_OPTIONAL, 'Pretty print the packages', false);
        $this->addOption(
            'only-directories',
            'd',
            InputOption::VALUE_OPTIONAL,
            'Only output the absolute directory paths as separate lines',
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = (array) $input->getArgument('files');

        $affected_packages = $this->package_repo->getAffected($files);
        $affected_packages = $affected_packages->filter(
            fn (Package $package): bool => ! Str::containsAny(
                $package->absolute_directory_path,
                ['src/Snicco/skeleton']
            )
        );

        $only_dirs = false !== $input->getOption('only-directories');

        if ($only_dirs) {
            $lines = [];
            foreach ($affected_packages as $affected_package) {
                $lines[] = $affected_package->absolute_directory_path;
            }

            $output->writeln($lines);

            return Command::SUCCESS;
        }

        $json_options = 0;

        if (false !== $input->getOption('pretty')) {
            $json_options = JSON_PRETTY_PRINT;
        }

        $output->writeln(
            $affected_packages->toJson(fn (Package $package): array => [
                'short_name' => $package->short_name,
                'vendor_name' => $package->vendor_name,
                'name' => $package->name,
                'abs_directory_path' => $package->absolute_directory_path,
                'composer_json_path' => $package->composer_path,
            ], $json_options)
        );

        return Command::SUCCESS;
    }
}
