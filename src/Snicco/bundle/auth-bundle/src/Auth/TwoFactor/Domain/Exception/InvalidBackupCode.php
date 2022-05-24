<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;

final class InvalidBackupCode extends InvalidArgumentException
{
}
