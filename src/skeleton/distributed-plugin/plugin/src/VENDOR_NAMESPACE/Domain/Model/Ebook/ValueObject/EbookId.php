<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject;

use VENDOR_NAMESPACE\Domain\Model\Common\Uuid;

/**
 * @psalm-immutable
 */
final class EbookId
{
    use Uuid;
}
