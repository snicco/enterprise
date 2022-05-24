<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication;

use PragmaRX\Google2FA\Google2FA;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\WPAuthenticate;
use Snicco\Enterprise\Bundle\Auth\AuthModule;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Infrastructure\Google2FaProvider;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;

final class AuthenticationModule extends AuthModule
{
    public function name(): string
    {
        return 'authentication';
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
       $this->addCommandHandler($config, [
           TwoFactorCommandHandler::class
       ]);
       
       $config->setIfMissing('snicco_auth.authentication.table_names', [
           '2fa_settings' => 'snicco_auth_2fa_settings',
       ]);
       
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();
        
        $container->shared(TwoFactorCommandHandler::class, fn() => new TwoFactorCommandHandler(
            $container[TwoFactorSettings::class],
            $container[OTPValidator::class]
        ));
        
        $container->shared(TwoFactorSettings::class, function () use($container,$config){
            
            /** @var non-empty-string $table_name */
            $table_name = $GLOBALS['wpdb']->prefix.$config->getString(
                'snicco_auth.authentication.table_names.2fa_settings'
                );
            
            return new TwoFactorSettingsBetterWPDB(
                $container[BetterWPDB::class],
                $table_name
            );
        });
        
        $container->shared(Google2FaProvider::class, fn() => new Google2FaProvider(
            new Google2FA(),
            $container[TwoFactorSettings::class]
        ));
        
        $container->shared(OTPValidator::class, fn() => $container[Google2FaProvider::class]);
        
        $container->shared(AuthenticationEventHandler::class, function () use ($container) {
            
            return new AuthenticationEventHandler(
                $container[TwoFactorSettings::class],
                $container[EventDispatcher::class]
            );
            
        });
        
    }

    public function boot(Kernel $kernel): void
    {
        $container = $kernel->container();

        $event_dispatcher = $container->make(EventDispatcher::class);
        $event_mapper = $container->make(EventMapper::class);

        $event_mapper->map('authenticate', WPAuthenticate::class, 9999);

        $event_dispatcher->subscribe(AuthenticationEventHandler::class);
    }
}
