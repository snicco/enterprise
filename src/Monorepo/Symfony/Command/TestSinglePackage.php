<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

use function escapeshellarg;
use function exec;
use function implode;
use function sprintf;

use const DIRECTORY_SEPARATOR;

final class TestSinglePackage extends Command
{
    public function __construct()
    {
        parent::__construct('test-package');
        $this->addArgument('package', InputArgument::REQUIRED, 'The path to the package');
        $this->addArgument('suites', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Suites to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);

        $package = (string) $input->getArgument('package');
        $package .= DIRECTORY_SEPARATOR . 'tests';

        $suites = (array) $input->getArgument('suites');
        Assert::allString($suites);

        $command = sprintf('vendor/bin/codecept run %s', $package);

        foreach ($suites as $suite) {
            $command .= sprintf(' --include-suite %s', $suite);
        }

        $style->section(escapeshellarg($command));

        // @todo improve this with proc_open if we use this frequently.
        exec($command, $test_output, $code);

        Assert::allString($test_output);

        $output->writeln(implode("\n", $test_output));

        return $code;
    }
}
