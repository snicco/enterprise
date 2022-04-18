<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command\Middleware;

use League\Tactician\Middleware;
use Snicco\Component\BetterWPDB\BetterWPDB;

final class BetterWPDBTransaction implements Middleware
{
    private BetterWPDB $better_wpdb;

    public function __construct(BetterWPDB $better_wpdb)
    {
        $this->better_wpdb = $better_wpdb;
    }

    /**
     * @param object $command
     *
     * @return mixed
     * @psalm-suppress MissingClosureReturnType
     */
    public function execute($command, callable $next)
    {
        return $this->better_wpdb->transactional(fn () => $next($command));
    }
}
