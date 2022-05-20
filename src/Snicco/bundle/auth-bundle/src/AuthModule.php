<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Kernel\Configuration\WritableConfig;

abstract class AuthModule
{
    abstract public function name() :string;
    
    public function configure(WritableConfig $config, Kernel $kernel) :void {
    
    }
    
    public function register(Kernel $kernel) :void {
    
    }
    
    public function boot(Kernel $kernel) :void {
    
    }
}