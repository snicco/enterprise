<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Password\Core\Exception;

use InvalidArgumentException;

final class InsufficientPasswordLength extends InvalidArgumentException
{
}
