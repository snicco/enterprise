<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Asset;

use InvalidArgumentException;
use function sprintf;

final class MissingManifestAsset extends InvalidArgumentException
{
    public static function forAsset(string $asset, string $manifest_file): self
    {
        return new self(sprintf('The manifest [%s] does not contain key [%s].', $manifest_file, $asset));
    }
}
