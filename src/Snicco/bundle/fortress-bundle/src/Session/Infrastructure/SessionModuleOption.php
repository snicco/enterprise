<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure;

final class SessionModuleOption
{
    /**
     * @var string
     */
    public const IDLE_TIMEOUT = 'idle_timeout';

    /**
     * @var string
     */
    public const ROTATION_INTERVAL = 'rotation_interval';

    /**
     * @var string
     */
    public const DB_TABLE_BASENAME = 'table_name';
}
