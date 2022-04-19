<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\WordPress\Translation;

use RuntimeException;

final class MissingTranslationID extends RuntimeException
{
    public static function forId(string $id): self
    {
        return new self("No translation available for id {$id}");
    }
}
