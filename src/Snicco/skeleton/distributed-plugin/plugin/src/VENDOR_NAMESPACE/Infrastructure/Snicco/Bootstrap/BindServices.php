<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Bootstrap;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbookService;
use VENDOR_NAMESPACE\Application\Ebook\CreateEbook\CreateEbookService;
use VENDOR_NAMESPACE\Infrastructure\ProductionServiceContainer;
use VENDOR_NAMESPACE\Infrastructure\ServiceContainer;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\ArchiveAllEbooksCommand;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\ArchiveEbookCommand;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\CreateEbookCommand;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\ListEbooksCommand;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller\EbookAPIController;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Http\Controller\EbookController;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Listeners\SendMail;
use Webmozart\Assert\Assert;

/**
 * This bootstrapper is responsible for binding services
 * into the container adapter so that they can be resolved by Snicco.
 * These fall mainly into the following categories:
 * - 1) HTTP Controllers
 * - 2) WP-CLI commands
 * - 3) Use cases / application services (if you are using the command bus)
 * - 4) Event listeners.
 */
final class BindServices implements Bootstrapper
{
    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        Assert::stringNotEmpty($config->getString('ebooks.table'));
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(ServiceContainer::class, function () use (
            $container,
            $config
        ): ProductionServiceContainer {
            /** @var non-empty-string $table */
            $table = $config->getString('ebooks.table');

            return new ProductionServiceContainer(
                $container[EventDispatcher::class],
                $container[BetterWPDB::class],
                $table
            );
        });

        $this->bindCommandHandlers($kernel);
        $this->bindHTTPControllers($kernel);
        $this->bindCLICommands($kernel);
        $this->bindEventListeners($kernel);
    }

    public function bootstrap(Kernel $kernel): void
    {
        $container = $kernel->container();
        $this->configureEvents($container->make(EventDispatcher::class));
    }

    private function bindCLICommands(Kernel $kernel): void
    {
        $container = $kernel->container();

        $container->shared(
            CreateEbookCommand::class,
            fn (): CreateEbookCommand => new CreateEbookCommand($container->make(CommandBus::class))
        );

        $container->shared(ListEbooksCommand::class, fn (): ListEbooksCommand => new ListEbooksCommand(
            $container->make(ServiceContainer::class)->availableEbooks()
        ));

        $container->shared(
            ArchiveEbookCommand::class,
            fn (): ArchiveEbookCommand => new ArchiveEbookCommand($container->make(CommandBus::class))
        );

        $container->shared(
            ArchiveAllEbooksCommand::class,
            fn (): ArchiveAllEbooksCommand => new ArchiveAllEbooksCommand($container->make(CommandBus::class))
        );
    }

    private function bindHTTPControllers(Kernel $kernel): void
    {
        $container = $kernel->container();

        $container->shared(EbookController::class, fn (): EbookController => new EbookController(
            $container->make(ServiceContainer::class)->availableEbooks(),
            $container[CommandBus::class],
        ));

        $container->shared(EbookAPIController::class, fn (): EbookAPIController => new EbookAPIController(
            $container->make(ServiceContainer::class)->availableEbooks(),
        ));
    }

    private function bindCommandHandlers(Kernel $kernel): void
    {
        $container = $kernel->container();
        $container->shared(
            ArchiveEbookService::class,
            fn (): ArchiveEbookService => $container->make(ServiceContainer::class)->archiveEbookService()
        );
        $container->shared(
            CreateEbookService::class,
            fn (): CreateEbookService => $container->make(ServiceContainer::class)->createEbookService()
        );
    }

    private function bindEventListeners(Kernel $kernel): void
    {
        $container = $kernel->container();

        $container->shared(SendMail::class, fn (): SendMail => new SendMail($container[UrlGenerator::class]));
    }

    private function configureEvents(EventDispatcher $event_dispatcher): void
    {
        $event_dispatcher->subscribe(SendMail::class);
    }
}
