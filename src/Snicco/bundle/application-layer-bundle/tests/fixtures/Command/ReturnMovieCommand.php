<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command;

final class ReturnMovieCommand
{
    public string $movie;

    public bool $returned = false;

    public function __construct(string $movie)
    {
        $this->movie = $movie;
    }
}
