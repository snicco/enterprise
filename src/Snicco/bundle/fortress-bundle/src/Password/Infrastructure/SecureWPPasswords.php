<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Password\Infrastructure;

use Defuse\Crypto\Core;
use Defuse\Crypto\Key;
use InvalidArgumentException;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\ConstantTime\Hex;
use ParagonIE\PasswordLock\PasswordLock;
use PasswordHash;
use Snicco\Component\BetterWPDB\BetterWPDB;
use WP_User;
use wpdb;

use function abs;
use function clean_user_cache;
use function sprintf;

final class SecureWPPasswords
{
    private BetterWPDB $db;

    private Key $key;

    private PasswordHash $wp_hasher;

    /**
     * @var non-empty-string
     */
    private string $users_table;

    private string $defuse_prefix;

    public function __construct(BetterWPDB $db, Key $key, PasswordHash $wp_hasher)
    {
        $this->db = $db;
        $this->key = $key;
        /** @psalm-suppress PropertyTypeCoercion */
        $this->users_table = $GLOBALS['wpdb']->users;
        $this->wp_hasher = $wp_hasher;
        $this->defuse_prefix = Hex::encode(Core::CURRENT_VERSION);
    }
    
    public static function alterTable(wpdb $wpdb): void
    {
        $users_table = $wpdb->users;
        // Can't use better wpdb here because it would crash due to the invalid default date value
        // for user_registered.
        $wpdb->query(sprintf('ALTER TABLE `%s` MODIFY COLUMN user_pass VARCHAR(300);', $users_table));
    }

    public function hash(string $plain_text_password): string
    {
        return PasswordLock::hashAndEncrypt($plain_text_password, $this->key);
    }

    public function update(string $plain_text_password, int $user_id): string
    {
        $hash = $this->hash($plain_text_password);

        $this->updateHash($hash, $user_id);

        return $hash;
    }

    public function check(string $plain_text_password, string $stored_hash, ?int $user_id = null): bool
    {
        $user_id = (null === $user_id || 0 === $user_id) ? null : abs($user_id);

        if ($this->isLegacyPassword($stored_hash)) {
            return $this->checkLegacyPassword(
                $plain_text_password,
                $stored_hash,
                $user_id
            );
        }

        $valid = PasswordLock::decryptAndVerify($plain_text_password, $stored_hash, $this->key);

        if ($user_id && PasswordLock::needsRehash($stored_hash, $this->key)) {
            // @codeCoverageIgnoreStart
            $this->update($plain_text_password, $user_id);
            // @codeCoverageIgnoreEnd
        }

        return $valid;
    }

    public function rotateEncryptionKey(WP_User $user, Key $new_key): string
    {
        $old_hash = $user->user_pass;

        if ($this->isLegacyPassword($old_hash)) {
            throw new InvalidArgumentException(sprintf(
                'User [%s] still has a legacy password that cant be rotated.',
                $user->ID
            ));
        }

        $new_hash = PasswordLock::rotateKey($old_hash, $this->key, $new_key);

        $user->user_pass = $new_hash;
        $this->updateHash($new_hash, $user->ID);

        return $new_hash;
    }

    private function isLegacyPassword(string $stored_hash): bool
    {
        return $this->defuse_prefix !== Binary::safeSubstr(
            $stored_hash,
            0,
            Binary::safeStrlen($this->defuse_prefix)
        );
    }

    private function checkLegacyPassword(string $plain_text_password, string $stored_hash, ?int $user_id): bool
    {
        /** @var bool $valid */
        $valid = $this->wp_hasher->CheckPassword($plain_text_password, $stored_hash);

        if (! $valid) {
            return false;
        }

        if ($user_id) {
            $this->update($plain_text_password, $user_id);
        }

        return true;
    }

    private function updateHash(string $new_hash, int $user_id): void
    {
        $this->db->updateByPrimary(
            $this->users_table,
            [
                'ID' => $user_id,
            ],
            [
                'user_pass' => $new_hash,
                'user_activation_key' => '',
            ]
        );

        clean_user_cache($user_id);
    }
}
