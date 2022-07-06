<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Symfony\Command;

use RuntimeException;
use Snicco\Enterprise\Monorepo\Package\Package;
use Snicco\Enterprise\Monorepo\Package\PackageRepository;
use Snicco\Enterprise\Monorepo\ValueObject\Tag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

use function chdir;
use function exec;
use function getcwd;
use function implode;
use function realpath;
use function sprintf;
use function sys_get_temp_dir;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

final class SplitPackage extends Command
{
    private PackageRepository $package_repo;

    private ?SymfonyStyle $output = null;

    private string $default_branch = 'master';

    public function __construct(PackageRepository $package_repo)
    {
        parent::__construct('split-package');
        $this->package_repo = $package_repo;
        $this->addArgument('package-dir', InputArgument::REQUIRED, 'The relative path to the package directory.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = new SymfonyStyle($input, $output);

        $package_dir = (string) realpath((string) $input->getArgument('package-dir'));
        $api_token = (string) ($_SERVER['GH_TOKEN'] ?? '');
        $commit_message = (string) ($_SERVER['COMMIT_MESSAGE'] ?? '');
        $tag = (string) ($_SERVER['TAG'] ?? '');
        $branch_name = (string) ($_SERVER['BRANCH'] ?? $this->default_branch);

        Assert::stringNotEmpty($package_dir, 'package directory cant be empty string');
        Assert::stringNotEmpty($api_token, '$_SERVER["GH_TOKEN"] can not be empty.');
        Assert::stringNotEmpty($commit_message, '$_SERVER["COMMIT_MESSAGE"] can not be empty.');
        Assert::stringNotEmpty($branch_name, '$_SERVER["BRANCH"] can not be empty.');

        $tag = empty($tag) ? null : new Tag($tag);

        $package = $this->package_repo->get($package_dir . '/composer.json');

        $this->output->title(sprintf('Splitting package %s...', $package->name));

        $this->splitRepo($package, $api_token, $commit_message, $branch_name, $tag);

        $this->output->success(sprintf('%s was split successfully.', $package->name));

        return Command::SUCCESS;
    }

    private function splitRepo(
        Package $package,
        string $api_token,
        string $commit_message,
        string $branch_name,
        ?Tag $tag
    ): void {
        Assert::notNull($this->output);
        $working_dir = getcwd();
        Assert::stringNotEmpty($working_dir);

        $org_name = $package->vendor_name;
        $repo_name = $package->short_name;
        $clone_dir = sys_get_temp_dir() . '/monorepo_split/clone_directory';

        $this->execWithNote('Removing contents of clone directory', sprintf('rm -rf %s', $clone_dir));

        $this->execWithNote(
            'Cloning remote into clone directory',
            sprintf('git clone -- https://%s@github.com/%s/%s.git %s', $api_token, $org_name, $repo_name, $clone_dir)
        );

        $this->output->section(
            sprintf('Changing working directory to clone directory %s from %s', $clone_dir, $working_dir)
        );
        Assert::notFalse(chdir($clone_dir));

        $remotes = $this->execWithNote(
            sprintf('Checking if remote branch %s exists', $branch_name),
            sprintf(
                'git ls-remote --heads https://%s@github.com/%s/%s.git %s',
                $api_token,
                $org_name,
                $repo_name,
                $branch_name
            )
        );

        if ([] === $remotes) {
            $this->output->warning(sprintf('Remote branch %s does not exist.', $branch_name));
            $this->execWithNote(
                sprintf('Creating and checking out new branch %s', $branch_name),
                sprintf('git checkout -b %s', $branch_name)
            );
        } else {
            $this->output->success(sprintf('Remote branch %s already exists.', $branch_name));
            $this->execWithNote(
                sprintf('Checking out existing branch %s', $branch_name),
                sprintf('git checkout %s', $branch_name)
            );
        }

        $this->execWithNote(
            'Copying contents of monorepo package',
            sprintf('cp -r %s %s', $package->absolute_directory_path . DIRECTORY_SEPARATOR . '.', '.'),
        );

        $files = $this->execWithNote('Checking if files are modified', 'git status --porcelain');

        $files_modified = ([] !== $files);

        if (! $files_modified) {
            $this->output->success('No files in this package have been changed.');
        } else {
            $this->execWithNote('Adding modified files', 'git add .');
            $this->execWithNote('Committing changes', sprintf("git commit -m '%s'", $commit_message));
            $this->execWithNote(
                'Pushing changes to remote repository',
                sprintf('git push origin %s', $branch_name)
            );
            $this->output->success(sprintf('Commit successfully pushed to remote branch %s.', $branch_name));
        }

        if (null !== $tag) {
            if (! $files_modified && $tag->isPatch()) {
                $this->output->success(
                    sprintf(
                        'Tag %s is a patch and unrelated to package %s. Not pushing tags...',
                        $tag->asString(),
                        $package->name
                    )
                );
            } else {
                $this->execWithNote(
                    'Creating tag',
                    sprintf('git tag %s -m "%s"', $tag->asString(), $commit_message)
                );
                $this->execWithNote('Pushing tags', sprintf('git push origin %s', $tag->asString()));
                $this->output->success(sprintf('Tag %s successfully pushed to remote.', $tag->asString()));
            }
        }

        $this->output->section(sprintf('Changing back to previous working directory %s', $working_dir));
        Assert::notFalse(chdir($working_dir));
    }

    /**
     * @return string[]
     */
    private function execWithNote(string $info, string $command): array
    {
        Assert::notNull($this->output);

        if ('' !== $info) {
            $info .= PHP_EOL;
        }

        $this->output->section($info . 'command: ' . $command);
        exec($command, $output, $code);

        if (0 !== $code) {
            throw new RuntimeException(sprintf('Command %s failed.', $command));
        }

        if ([] !== $output) {
            /** @var string[] $output */
            $command_output = implode(PHP_EOL, $output);
            $this->output->writeln($command_output);
        } else {
            $this->output->newLine();
        }

        return $output;
    }
}
