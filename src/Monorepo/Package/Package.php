<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\Package;

use Snicco\Component\StrArr\Str;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJson;
use Symplify\SmartFileSystem\SmartFileInfo;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_keys;

/**
 * @psalm-immutable
 */
final class Package
{
    /**
     * @var non-empty-string
     */
    public string $absolute_directory_path;

    /**
     * @var non-empty-string
     */
    public string $vendor_name;

    /**
     * @var non-empty-string
     */
    public string $short_name;

    /**
     * @var non-empty-string
     */
    public string $name;

    /**
     * @var string[]
     */
    public array $first_party_dependencies;

    /**
     * @var non-empty-string
     */
    public string $composer_path;

    private ComposerJson $composer_json;

    /**
     * @psalm-suppress ImpureMethodCall
     * @psalm-suppress ImpureFunctionCall
     */
    public function __construct(ComposerJson $composer_json)
    {
        $this->composer_json = $composer_json;
        $file_info = $composer_json->getFileInfo();
        Assert::isInstanceOf($file_info, SmartFileInfo::class);

        $abs_dir = $file_info->getRealPathDirectory();
        Assert::stringNotEmpty($abs_dir);
        $this->absolute_directory_path = $abs_dir;

        $name = $this->composer_json->getName();
        Assert::stringNotEmpty($name);
        $this->name = $name;

        $vendor_name = $this->composer_json->getVendorName();
        Assert::stringNotEmpty($vendor_name);
        $this->vendor_name = $vendor_name;

        $short_name = $this->composer_json->getShortName();
        Assert::stringNotEmpty($short_name);
        $this->short_name = $short_name;

        $require = array_keys($this->composer_json->getRequire());
        $require_dev = array_keys($this->composer_json->getRequireDev());

        $deps = [...$require, ...$require_dev];
        Assert::allString($deps);

        $this->first_party_dependencies = array_filter(
            $deps,
            fn (string $name): bool => Str::startsWith($name, $this->vendor_name . '/')
        );
    
        $path = $file_info->getRealPath();
        Assert::stringNotEmpty($path);
        $this->composer_path = $path;
    }
}
