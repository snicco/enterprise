<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Common;

use Throwable;

interface UserFacingDomainError extends Throwable
{
    public function translationID(): string;

    /**
     * @return array<string,string|int>
     */
    public function translationParameters(): array;
}
