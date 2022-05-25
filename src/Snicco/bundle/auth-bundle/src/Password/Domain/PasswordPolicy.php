<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Password\Domain;

use ParagonIE\ConstantTime\Binary;
use Snicco\Enterprise\AuthBundle\Password\Domain\Exception\InsufficientPasswordEntropy;
use Snicco\Enterprise\AuthBundle\Password\Domain\Exception\InsufficientPasswordLength;
use Snicco\Enterprise\AuthBundle\Password\Domain\Exception\PasswordLengthExceeded;
use ZxcvbnPhp\Zxcvbn;

final class PasswordPolicy
{
    private Zxcvbn $zxcvbn;

    private int $upper_limit_for_entropy_check;

    public function __construct(int $upper_limit_for_entropy_check = 100)
    {
        $this->zxcvbn = new Zxcvbn();
        $this->upper_limit_for_entropy_check = $upper_limit_for_entropy_check;
    }

    /**
     * @param string[] $context
     */
    public function check(string $plain_text_password, array $context = []): void
    {
        $length = Binary::safeStrlen($plain_text_password);

        if ($length < 12) {
            throw new InsufficientPasswordLength('Passwords must have at least 12 characters.');
        }

        if ($length > 4096) {
            throw new PasswordLengthExceeded('Password can not have more than 4096 characters');
        }

        // Zxcvbn will take forever on really long password.
        if ($length > $this->upper_limit_for_entropy_check) {
            return;
        }

        /** @var array{score: int} $check */
        $check = $this->zxcvbn->passwordStrength($plain_text_password, $context);

        if ($check['score'] < 3) {
            throw new InsufficientPasswordEntropy();
        }
    }
}
