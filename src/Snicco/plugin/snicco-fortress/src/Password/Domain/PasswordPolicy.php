<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Password\Domain;

use ParagonIE\ConstantTime\Binary;
use Snicco\Enterprise\Fortress\{
    Password\Domain\Exception\InsufficientPasswordEntropy,
    Password\Domain\Exception\InsufficientPasswordLength,
    Password\Domain\Exception\PasswordLengthExceeded};
use ZxcvbnPhp\Zxcvbn;

final class PasswordPolicy
{
    /**
     * @var int
     */
    private const MIN_PASSWORD_LENGTH = 12;

    /**
     * @var int
     */
    private const MAX_PASSWORD_LENGTH = 4096;

    /**
     * @var int
     */
    private const MIN_ZXCVBN_SCORE = 3;

    private Zxcvbn $zxcvbn;

    private int $upper_character_limit_for_entropy_check;

    public function __construct(int $upper_character_limit_for_entropy_check = 100)
    {
        $this->zxcvbn = new Zxcvbn();
        $this->upper_character_limit_for_entropy_check = $upper_character_limit_for_entropy_check;
    }

    /**
     * @param string[] $context
     */
    public function check(string $plain_text_password, array $context = []): void
    {
        $length = Binary::safeStrlen($plain_text_password);

        if ($length < self::MIN_PASSWORD_LENGTH) {
            throw new InsufficientPasswordLength('Passwords must have at least 12 characters.');
        }

        if ($length > self::MAX_PASSWORD_LENGTH) {
            throw new PasswordLengthExceeded('Password can not have more than 4096 characters');
        }

        // Zxcvbn will take forever on really long password.
        // https://github.com/bjeavons/zxcvbn-php/issues/56
        if ($length > $this->upper_character_limit_for_entropy_check) {
            return;
        }

        /** @var array{score: int} $check */
        $check = $this->zxcvbn->passwordStrength($plain_text_password, $context);

        if ($check['score'] < self::MIN_ZXCVBN_SCORE) {
            throw new InsufficientPasswordEntropy();
        }
    }
}
