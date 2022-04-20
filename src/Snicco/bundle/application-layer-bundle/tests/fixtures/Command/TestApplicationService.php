<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command;

use stdClass;

final class TestApplicationService
{
    public stdClass $class;

    public function __construct(stdClass $class)
    {
        $this->class = $class;
    }

    public function __invoke(RentMovieCommand $command): void
    {
        $command->handled = true;
    }

    public function returnMovie(ReturnMovieCommand $command): void
    {
        $command->returned = true;
    }

    public function someMethodWithNativeTypeNotIncludedInCommandMap(int $foo): int
    {
        return $foo * 2;
    }
}
