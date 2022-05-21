<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\BannableEvent;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Event\AuthCookieBadHash;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Event\WPLoginFailed;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Fail2Ban;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\PHPSyslogger;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Syslogger;

use const LOG_AUTH;
use const LOG_PID;

final class Fail2BanModule extends AuthModule
{
    public function name(): string
    {
        return 'fail2ban';
    }

    public function shouldRun(Environment $env): bool
    {
        // @codeCoverageIgnoreStart
        // @codeCoverageIgnoreEnd
        return ! ($env->isCli() && ! $env->isTesting());
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->setIfMissing('snicco_auth.fail2ban', [
            'daemon' => 'snicco_auth',
            'flags' => LOG_PID,
            'facility' => LOG_AUTH,
        ]);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(Fail2Ban::class, fn (): Fail2Ban => new Fail2Ban(
            $container[Syslogger::class] ?? new PHPSyslogger(),
            [
                'daemon' => $config->getString('snicco_auth.fail2ban.daemon'),
                'flags' => $config->getInteger('snicco_auth.fail2ban.flags'),
                'facility' => $config->getInteger('snicco_auth.fail2ban.facility'),
            ]
        ));
    }

    public function boot(Kernel $kernel): void
    {
        $container = $kernel->container();

        $event_dispatcher = $container->make(EventDispatcher::class);
        $event_mapper = $container->make(EventMapper::class);

        $event_dispatcher->listen(BannableEvent::class, [Fail2Ban::class, 'report']);

        $event_mapper->mapFirst('wp_login_failed', WPLoginFailed::class);
        $event_mapper->mapFirst('auth_cookie_bad_hash', AuthCookieBadHash::class);
    }
}
