<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Password\Domain\Exception;

use InvalidArgumentException;

final class InsufficientPasswordEntropy extends InvalidArgumentException
{
}
