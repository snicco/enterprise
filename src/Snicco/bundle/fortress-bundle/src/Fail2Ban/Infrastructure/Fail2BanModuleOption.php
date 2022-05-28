<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Fail2Ban\Infrastructure;

final class Fail2BanModuleOption
{
    /**
     * @var string
     */
    public const DAEMON = 'daemon';

    /**
     * @var string
     */
    public const FLAGS = 'flags';

    /**
     * @var string
     */
    public const FACILITY = 'facility';
}
