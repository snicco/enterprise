<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\User\Domain;

use InvalidArgumentException;

final class UserNotFound extends InvalidArgumentException
{
}
