<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\InvalidBackupCode;

use Webmozart\Assert\Assert;

use function array_map;
use function base64_encode;
use function explode;
use function password_hash;
use function password_verify;
use function random_bytes;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;

use const PASSWORD_BCRYPT;

/**
 * @template-implements IteratorAggregate<non-empty-string>
 */
final class BackupCodes implements IteratorAggregate
{
    /**
     * @var non-empty-list<non-empty-string>
     */
    private array $hashed_codes;

    /**
     * @param non-empty-array<non-empty-string> $hashed_codes
     */
    private function __construct(array $hashed_codes)
    {
        foreach ($hashed_codes as $hashed_code) {
            if (60 !== strlen($hashed_code)) {
                throw new InvalidArgumentException('One code does not have the expected length');
            }
        }

        $this->hashed_codes = $hashed_codes;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public static function generate(): array
    {
        return [
            self::random8CharString() . '-' . self::random8CharString(),
            self::random8CharString() . '-' . self::random8CharString(),
            self::random8CharString() . '-' . self::random8CharString(),
            self::random8CharString() . '-' . self::random8CharString(),
            self::random8CharString() . '-' . self::random8CharString(),
            self::random8CharString() . '-' . self::random8CharString(),
            self::random8CharString() . '-' . self::random8CharString(),
            self::random8CharString() . '-' . self::random8CharString(),
        ];
    }

    /**
     * @param non-empty-list<non-empty-string>|null $plain_codes
     */
    public static function fromPlainCodes(?array $plain_codes = null): self
    {
        $hashed_codes = array_map(function (string $key): string {
            $parts = explode('-', $key);

            if (! isset($parts[0]) || ! isset($parts[1])) {
                throw new InvalidArgumentException(sprintf('Invalid key [%s].', $key));
            }

            if (8 !== strlen($parts[0]) || 8 !== strlen($parts[1])) {
                throw new InvalidArgumentException(sprintf('Invalid key [%s].', $key));
            }

            return self::hash($key);
        }, $plain_codes ?: self::generate());

        return new self($hashed_codes);
    }

    /**
     * @param non-empty-list<non-empty-string> $hashed_codes
     */
    public static function fromHashedCodes(array $hashed_codes): self
    {
        return new self($hashed_codes);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->hashed_codes);
    }

    /**
     * @throws InvalidBackupCode
     */
    public function revoke(string $user_provided_code): void
    {
        foreach ($this->hashed_codes as $index => $hashed_code) {
            if (password_verify($user_provided_code, $hashed_code)) {
                unset($this->hashed_codes[$index]);

                return;
            }
        }

        throw new InvalidBackupCode(sprintf('The backup code %s is not valid.', $user_provided_code));
    }

    private static function random8CharString(): string
    {
        $length = 8;
        $string = '';

        while (($len = strlen($string)) < $length) {
            /** @var positive-int $size */
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= (string) substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * @return non-empty-string
     */
    private static function hash(string $string): string
    {
        $hashed = password_hash($string, PASSWORD_BCRYPT);

        Assert::stringNotEmpty($hashed, 'password_hash returned non-string. This should not happen.');

        return $hashed;
    }
}
