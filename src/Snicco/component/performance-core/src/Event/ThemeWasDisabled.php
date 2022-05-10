<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Enterprise\Component\Condition\Context;

final class ThemeWasDisabled implements ExposeToWP, Event
{
    use ClassAsPayload;

    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function context(): Context
    {
        return $this->context;
    }

    public function name(): string
    {
        return 'snicco/performance:disabled_theme';
    }
}
