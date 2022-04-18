<?php

declare(strict_types=1);

use Snicco\Enterprise\Bundle\ApplicationLayer\CommandBusOption;
use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\TestApplicationService;

return [
    CommandBusOption::APPLICATION_SERVICES => [TestApplicationService::class],
];
