<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\ValueObject;

use Webmozart\Assert\Assert;

use function rtrim;
use function trim;

use const DIRECTORY_SEPARATOR;

/**
 * @psalm-immutable
 */
final class RepositoryRoot
{
    /**
     * @var non-empty-string
     */
    public string $dir;

    public function __construct(string $dir)
    {
        Assert::stringNotEmpty($dir);
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        Assert::readable($dir);
        Assert::directory($dir);
        $this->dir = $dir;
    }

    /**
     * @return non-empty-string
     */
    public function append(string $file_name_or_path): string
    {
        return $this->dir . trim($file_name_or_path, DIRECTORY_SEPARATOR);
    }
    
    public function __toString() :string
    {
        return $this->dir;
    }
    
}
