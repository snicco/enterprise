<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

use function sprintf;

final class DisablingPlugin implements Event, ExposeToWP
{
    use ClassAsPayload;

    public bool $proceed = true;

    private string $plugin_id;

    public function __construct(string $plugin_id)
    {
        $this->plugin_id = $plugin_id;
    }

    public function pluginId(): string
    {
        return $this->plugin_id;
    }

    public function payload(): self
    {
        return $this;
    }

    public function name(): string
    {
        return sprintf('snicco/performance:disabling_%s', $this->plugin_id);
    }
}
