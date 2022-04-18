<?php

declare(strict_types=1);

use Snicco\Enterprise\Bundle\ApplicationLayer\CommandBusOption;

return [
    // A list of all classes that handle at least one command via the command bus.
    CommandBusOption::APPLICATION_SERVICES => [],
];
