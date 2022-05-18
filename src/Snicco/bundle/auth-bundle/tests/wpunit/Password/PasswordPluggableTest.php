<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Password;

use PasswordHash;
use LogicException;
use RuntimeException;
use Defuse\Crypto\Key;
use Codeception\Test\Unit;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Enterprise\Bundle\Auth\Password\SecureWPPasswords;

use Snicco\Enterprise\Bundle\Auth\Password\PasswordPluggable;

use function class_exists;

final class PasswordPluggableTest extends WPTestCase
{
    
    private SecureWPPasswords $password;
    
    protected function setUp() :void
    {
        parent::setUp();
        if ( ! class_exists(PasswordHash::class)) {
            /**
             * @psalm-suppress MissingFile
             */
            require_once ABSPATH.WPINC.'/class-phpass.php';
        }
        $this->password = new SecureWPPasswords(
            BetterWPDB::fromWpdb(),
            Key::createNewRandomKey(),
            new PasswordHash(8, true)
        );
    }
    
    /**
     * @test
     */
    public function test_get_set_instance() :void
    {
        try {
            PasswordPluggable::securePasswords();
            throw new RuntimeException('Should have failed');
        }catch (LogicException $e) {
            //
        }
        
        PasswordPluggable::set($this->password);
        
        $this->assertSame($this->password, PasswordPluggable::securePasswords());
    
        try {
            PasswordPluggable::set($this->password);
            throw new RuntimeException('Should have failed');
        }catch (LogicException $e) {
            //
        }
        
    }
    
}