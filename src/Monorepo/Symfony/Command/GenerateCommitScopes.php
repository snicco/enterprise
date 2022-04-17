<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use LogicException;
use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

use function count;
use function file_put_contents;
use function json_encode;
use function sort;
use function sprintf;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class GenerateCommitScopes extends Command
{
    private PackageRepository $package_repo;

    private RepositoryRoot    $repository_root;

    public function __construct(PackageRepository $package_repo, RepositoryRoot $repository_root)
    {
        parent::__construct('generate-commit-scopes');
        $this->package_repo = $package_repo;
        $this->repository_root = $repository_root;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfony_style = new SymfonyStyle($input, $output);

        $extra_scopes = ['monorepo', '*'];

        $component = [];
        $bundle = [];
        $skeleton = [];
        $plugin = [];

        foreach ($this->package_repo->getAll() as $package) {
            $dir = $package->absolute_directory_path;
            $short_name = $package->short_name;

            if (Str::contains($dir, 'bundle')) {
                $bundle[] = $short_name;
            } elseif (Str::contains($dir, 'component')) {
                $component[] = $short_name;
            } elseif (Str::contains($dir, 'plugin')) {
                $plugin[] = $short_name;
            } elseif (Str::contains($dir, 'skeleton')) {
                $skeleton[] = $short_name;
            } else {
                throw new LogicException(sprintf(
                    'Package %s does not belong to any package source directory.',
                    $short_name
                ));
            }
        }

        sort($component);
        sort($bundle);
        sort($skeleton);
        sort($plugin);

        $merged = [...$extra_scopes, ...$component, ...$bundle, ...$skeleton, ...$plugin];

        $json = json_encode($merged, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        $res = file_put_contents($this->repository_root->append('commit-scopes.json'), (string) $json);
        Assert::notFalse($res, 'Could not update commit scopes.');

        $symfony_style->success(sprintf('Dumped %d commit scopes.', count($merged)));

        return Command::SUCCESS;
    }
}
