<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use RuntimeException;
use Defuse\Crypto\Key;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Configuration\Config;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Password\PasswordEventHandler;
use Snicco\Enterprise\Bundle\Auth\Session\Event\SessionActivityRecorded;
use Snicco\Enterprise\Bundle\Auth\Session\SessionEventHandler;
use Snicco\Enterprise\Bundle\Auth\Session\SessionRepository;
use Snicco\Enterprise\Bundle\Auth\Session\TimeoutResolver;
use Snicco\Enterprise\Bundle\Auth\Session\WPAuthSessions;
use WP_User_Meta_Session_Tokens;

use function in_array;
use function array_map;
use function add_filter;
use function sprintf;

use function array_filter;

use const PHP_INT_MAX;

final class AuthBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/auth-bundle';

    /** @var array<class-string<AuthModule>> */
    private const MODULES = [
        SessionModule::class,
        PasswordModule::class
    ];
    
    /**
     * @var AuthModule[]
     */
    private array $modules;
    
    /**
     * @var null|AuthModule[]
     */
    private ?array $enabled_modules = null;
    
    public function __construct() {
        $this->modules = array_map(fn(string $class) => new $class, self::MODULES);
    }
    
    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->setIfMissing('snicco_auth.features', [
            'password',
            'session',
        ]);
    
        if($kernel->env()->isTesting()) {
            $config->setIfMissing(
                'snicco_auth.encryption_secret',
                Key::createNewRandomKey()->saveToAsciiSafeString()
            );
        }
        
        foreach ($this->enabledModules($config) as $module) {
            $module->configure($config, $kernel);
        }
        
    }

    public function register(Kernel $kernel): void
    {
        foreach ($this->enabledModules($kernel->config()) as $module) {
            $module->register($kernel);
        }
    }

    public function bootstrap(Kernel $kernel): void
    {
        foreach ($this->enabledModules($kernel->config()) as $module) {
            $module->boot($kernel);
        }
    }

    public function alias(): string
    {
        return self::ALIAS;
    }
    
    /**
     * @return AuthModule[]
     */
    public function enabledModules(Config $config) :array
    {
        if(null === $this->enabled_modules) {
            $enabled = $config->getListOfStrings('snicco_auth.features');
    
            $this->enabled_modules = array_filter(
                $this->modules,
                fn(AuthModule $module) => in_array($module->name(), $enabled, true)
            );
        }
        
        return $this->enabled_modules;
    }
    
}
