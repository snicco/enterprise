<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Password;

use Codeception\TestCase\WPTestCase;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use InvalidArgumentException;
use PasswordHash;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Enterprise\Bundle\Auth\Password\Core\SecureWPPasswords;
use WP_User;

use function class_exists;
use function clean_user_cache;

use const ABSPATH;
use const WPINC;

/**
 * @internal
 */
final class SecureWPPasswordsTest extends WPTestCase
{
    private SecureWPPasswords $password;

    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(PasswordHash::class)) {
            /**
             * @psalm-suppress MissingFile
             */
            require_once ABSPATH . WPINC . '/class-phpass.php';
        }

        $this->password = new SecureWPPasswords(
            BetterWPDB::fromWpdb(),
            Key::createNewRandomKey(),
            new PasswordHash(8, true)
        );
        $this->password->alterTable($GLOBALS['wpdb']);
    }

    /**
     * @test
     */
    public function that_a_password_can_be_hashed(): void
    {
        $hashed1 = $this->password->hash('foobar');
        $this->assertNotSame('foobar', $hashed1);

        $hashed2 = $this->password->hash('foobar');
        $this->assertNotSame('foobar', $hashed2);

        $this->assertNotSame($hashed2, $hashed1);
    }

    /**
     * @test
     */
    public function that_a_password_can_be_set_for_a_user(): void
    {
        //$initial = 80;
        //
        //for ($i=1; $i<20; $i++) {
        //    $input_length = $initial+$i;
        //    $hashed = $this->password->hash(str_repeat('x', $input_length ));
        //    echo "Input length: $input_length\n";
        //    echo "Output length: " .strlen($hashed);
        //    echo "\n\n";
        //}

        $hashed = $this->password->update('foobar', 1);
        $this->assertNotSame('foobar', $hashed);

        $user = new WP_User(1);

        $this->assertSame($hashed, $user->user_pass);
        $this->assertSame('', $user->user_activation_key);
    }

    /**
     * @test
     */
    public function that_a_password_can_be_checked(): void
    {
        $hashed = $this->password->hash('foobar');

        $this->assertTrue($this->password->check('foobar', $hashed));
        $this->assertFalse($this->password->check('foobaz', $hashed));
    }

    /**
     * @test
     */
    public function that_a_legacy_password_can_be_checked(): void
    {
        $user = new WP_User(1);
        $legacy_password = $user->user_pass;

        $this->assertTrue($this->password->check('password', $legacy_password));
    }

    /**
     * @test
     */
    public function that_a_legacy_password_is_rehashed(): void
    {
        $user = new WP_User(1);
        $legacy_password = $user->user_pass;

        $this->assertTrue($this->password->check('password', $legacy_password, 1));

        clean_user_cache(1);

        $user = new WP_User(1);

        $this->assertNotSame($legacy_password, $user->user_pass);
    }

    /**
     * @test
     */
    public function that_a_password_can_be_encrypted_with_a_different_key(): void
    {
        $this->password->update('password', 1);

        $prev_pass = (new WP_User(1))->user_pass;

        $new_key = Key::createNewRandomKey();

        $new_hash = $this->password->rotateEncryptionKey($user = new WP_User(1), $new_key);

        $this->assertNotSame($prev_pass, $new_hash);
        $this->assertSame($new_hash, $user->user_pass);

        $new_instance = new SecureWPPasswords(
            BetterWPDB::fromWpdb(),
            $new_key,
            new PasswordHash(8, true)
        );

        $this->assertTrue($new_instance->check('password', $new_hash));

        $this->expectException(WrongKeyOrModifiedCiphertextException::class);
        $this->password->check('password', $new_hash);
    }

    /**
     * @test
     */
    public function that_a_legacy_password_cant_be_rotated(): void
    {
        $new_key = Key::createNewRandomKey();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User [1] still has a legacy password that cant be rotated.');

        $this->password->rotateEncryptionKey(new WP_User(1), $new_key);
    }
}
