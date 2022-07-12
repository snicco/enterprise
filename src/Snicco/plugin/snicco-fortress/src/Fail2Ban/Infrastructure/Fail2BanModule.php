<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Fail2Ban\Infrastructure;

use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Fortress\Fail2Ban\Application\Fail2BanCommandHandler;
use Snicco\Enterprise\Fortress\Fail2Ban\Domain\Syslogger;
use Snicco\Enterprise\Fortress\Fail2Ban\Infrastructure\MappedEvent\AuthCookieBadHash;
use Snicco\Enterprise\Fortress\Fail2Ban\Infrastructure\MappedEvent\WPLoginFailed;
use Snicco\Enterprise\Fortress\Shared\Infrastructure\FortressModule;

final class Fail2BanModule extends FortressModule
{
    /**
     * @var string
     */
    public const NAME = 'fail2ban';

    public function name(): string
    {
        return self::NAME;
    }

    public function shouldRun(Environment $env): bool
    {
        return ! ($env->isCli() && ! $env->isTesting());
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $this->addCommandHandler($config, [
            Fail2BanCommandHandler::class,
        ]);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(
            Fail2BanCommandHandler::class,
            fn (): Fail2BanCommandHandler => new Fail2BanCommandHandler(
                $container[Syslogger::class] ?? new PHPSyslogger(),
                [
                    'daemon' => $config->getString('fortress.fail2ban.' . Fail2BanModuleOption::DAEMON),
                    'flags' => $config->getInteger('fortress.fail2ban.' . Fail2BanModuleOption::FLAGS),
                    'facility' => $config->getInteger('fortress.fail2ban.' . Fail2BanModuleOption::FACILITY),
                ]
            )
        );

        $container->shared(
            Fail2BanEventHandler::class,
            fn (): Fail2BanEventHandler => new Fail2BanEventHandler(
                $container[CommandBus::class]
            )
        );
    }

    public function boot(Kernel $kernel): void
    {
        $container = $kernel->container();

        $event_dispatcher = $container->make(EventDispatcher::class);
        $event_mapper = $container->make(EventMapper::class);

        $event_dispatcher->listen(BanworthyEvent::class, [Fail2BanEventHandler::class, 'onBanWorthyEvent']);

        $event_mapper->mapFirst('wp_login_failed', WPLoginFailed::class);
        $event_mapper->mapFirst('auth_cookie_bad_hash', AuthCookieBadHash::class);
    }
}
