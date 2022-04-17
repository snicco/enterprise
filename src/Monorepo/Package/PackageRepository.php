<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Package;

use DirectoryIterator;
use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Monorepo\ValueObject\RepositoryRoot;
use Symfony\Component\DependencyInjection\Container;
use Symplify\ComposerJsonManipulator\ComposerJsonFactory;
use Symplify\ComposerJsonManipulator\FileSystem\JsonFileManager;
use Symplify\ComposerJsonManipulator\Json\JsonCleaner;
use Symplify\ComposerJsonManipulator\Json\JsonInliner;
use Symplify\PackageBuilder\Parameter\ParameterProvider;
use Symplify\SmartFileSystem\SmartFileSystem;
use Webmozart\Assert\Assert;

use function array_map;
use function is_file;
use function realpath;

use const DIRECTORY_SEPARATOR;

final class PackageRepository
{
    private ComposerJsonFactory $json;

    /**
     * @var non-empty-string[]
     */
    private array          $package_source_dirs;

    private RepositoryRoot $repository_root;

    /**
     * @param non-empty-string[] $package_source_dirs
     */
    public function __construct(array $package_source_dirs, RepositoryRoot $repository_root)
    {
        Assert::allDirectory($package_source_dirs);
        Assert::allReadable($package_source_dirs);
        $this->json = new ComposerJsonFactory(
            new JsonFileManager(
                new SmartFileSystem(),
                new JsonCleaner(),
                new JsonInliner(new ParameterProvider(new Container()))
            )
        );
        $this->package_source_dirs = $package_source_dirs;
        $this->repository_root = $repository_root;
    }

    /**
     * @param non-empty-string $path_to_package_composer_json
     */
    public function get(string $path_to_package_composer_json): Package
    {
        Assert::file($path_to_package_composer_json);

        $json = $this->json->createFromFilePath($path_to_package_composer_json);

        return new Package($json);
    }

    public function getAll(): PackageCollection
    {
        $packages = [];

        foreach ($this->package_source_dirs as $package_source_dir) {
            foreach ($this->allDirsInDir($package_source_dir) as $package_dir) {
                $packages[] = $this->get($package_dir . DIRECTORY_SEPARATOR . 'composer.json');
            }
        }

        return new PackageCollection($packages);
    }

    public function getAffected(array $changed_files): PackageCollection
    {
        $modified_files_abs_path = array_map(
            fn (string $file): string => $this->makeAbsolute($file),
            $changed_files
        );

        $all_packages = $this->getAll();

        $directly_affected_packages = $all_packages->filter(function (Package $package) use (
            $modified_files_abs_path
        ): bool {
            foreach ($modified_files_abs_path as $file) {
                if (Str::startsWith($file, $package->absolute_directory_path)) {
                    return true;
                }
            }

            return false;
        });

        $indirectly_affected_packages = (new GetDirectlyDependentPackages())(
            $directly_affected_packages,
            $all_packages
        );

        return $directly_affected_packages->merge($indirectly_affected_packages);
    }

    /**
     * @param non-empty-string $package_source_dir
     *
     * @return string[]
     */
    private function allDirsInDir(string $package_source_dir): array
    {
        $sub_dirs = [];

        $dir = new DirectoryIterator($package_source_dir);
        foreach ($dir as $file_info) {
            if ($file_info->isDir() && ! $file_info->isDot()) {
                $sub_dirs[] = $file_info->getRealPath();
            }
        }

        return $sub_dirs;
    }

    private function makeAbsolute(string $file): string
    {
        $realpath = (string) realpath($file);

        if (is_file($realpath)) {
            return $realpath;
        }

        // Don't throw exceptions here if a file does not exist, because file deletion produced
        // by the git diff command is a reason to test the package.
        return $this->repository_root->append($file);
    }
}
