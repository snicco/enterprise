<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\WordPress;

use Snicco\Component\Kernel\DIContainer;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use Webmozart\Assert\Assert;

/**
 * This class can be used to expose the public API of the plugin to other
 * developers/plugins. Unfortunately, there is no better way then to use a
 * globally available singleton for this purpose. You should not use this class
 * anywhere in the Application/Domain layer.
 *
 * You should only expose specific services and god forbid never expose your di
 * container.
 */
final class VENDOR_NAMESPACE
{
    private DIContainer $container;

    private static ?VENDOR_NAMESPACE $instance = null;

    private function __construct(DIContainer $container)
    {
        $this->container = $container;
    }

    /**
     * @interal
     */
    public static function expose(DIContainer $container): void
    {
        Assert::null(VENDOR_NAMESPACE::$instance, 'The VENDOR_TITLE plugin can only be exposed once.');
        VENDOR_NAMESPACE::$instance = new self($container);
    }

    public static function plugin(): VENDOR_NAMESPACE
    {
        Assert::isInstanceOf(
            VENDOR_NAMESPACE::$instance,
            VENDOR_NAMESPACE::class,
            'The VENDOR_TITLE plugin is not available yet.'
        );

        return VENDOR_NAMESPACE::$instance;
    }

    public function handle(object $command): void
    {
        $this->container->make(CommandBus::class)->handle($command);
    }

    public function availableEbooks(): AvailableEbooks
    {
        return $this->container->make(AvailableEbooks::class);
    }
}
