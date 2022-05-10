<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Enterprise\Component\Condition\Context;

final class DisablingTheme implements Event, ExposeToWP
{
    use ClassAsPayload;

    public bool $proceed = true;

    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function payload(): self
    {
        return $this;
    }

    public function name(): string
    {
        return 'snicco/performance:disabling_theme';
    }

    public function context(): Context
    {
        return $this->context;
    }
}
