<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\Exception;

use InvalidArgumentException;

final class InvalidBackupCode extends InvalidArgumentException
{
}
