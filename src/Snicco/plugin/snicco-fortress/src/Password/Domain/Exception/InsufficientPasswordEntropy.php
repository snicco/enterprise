<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Password\Domain\Exception;

use InvalidArgumentException;

final class InsufficientPasswordEntropy extends InvalidArgumentException
{
}
