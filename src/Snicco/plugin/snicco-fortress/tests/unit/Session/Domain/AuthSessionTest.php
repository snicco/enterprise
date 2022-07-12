<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\unit\Session\Domain;

use Codeception\Test\Unit;
use InvalidArgumentException;
use RuntimeException;
use Snicco\Enterprise\Fortress\Session\Domain\AuthSession;

use function time;

/**
 * @psalm-suppress InternalMethod
 *
 * @internal
 */
final class AuthSessionTest extends Unit
{
    /**
     * @test
     */
    public function that_it_can_be_constructed_from_array_data(): void
    {
        $session = AuthSession::fromArrayDataForStorage('foo', 1, [
            '__snicco_last_active' => $now = time(),
            '__snicco_last_rotated' => $now,
            'expiration' => $now + 20,
        ]);

        $this->assertSame('foo', $session->hashedToken());

        $this->assertSame(1, $session->userId());

        $this->assertSame($now, $session->lastActivity());
        $this->assertSame($now, $session->lastRotation());
        $this->assertSame($now + 20, $session->expiresAt());
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_keys_are_missing(): void
    {
        try {
            AuthSession::fromArrayDataForStorage('foo', 1, [
                //'expiration' => $now + 20,
            ]);

            throw new RuntimeException('Should have failed');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('expiration', $e->getMessage());
        }
    }
}
