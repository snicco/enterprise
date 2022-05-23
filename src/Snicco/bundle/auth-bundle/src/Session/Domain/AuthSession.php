<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Domain;

use InvalidArgumentException;

use function time;
use function is_int;
use function is_string;

/**
 * @internal
 * @psalm-internal Snicco\Enterprise\Bundle\Auth
 */
final class AuthSession
{
    
    private string $hashed_token;
    
    private int $user_id;
    
    private array $data;
    
    private bool $fully_authenticated = true;
    
    private int $expires_at;
    
    private int $last_activity;
    
    private int $last_rotation;
    
    /**
     * @param  mixed[]  $data
     */
    public function __construct(
        string $hashed_token,
        int $user_id,
        int $last_activity,
        int $last_rotation,
        array $data
    ) {
        // We don't need the expiration here, but if the user mistakenly unsets it
        // he will be logged out if stops using this bundle.
        if ( ! isset($data['expiration']) || ! is_int($data['expiration'])) {
            throw new InvalidArgumentException('The session data must contain an expiration timestamp.');
        }
        
        $this->hashed_token = $hashed_token;
        $this->user_id = $user_id;
        $this->data = $data;
        $this->expires_at = $data['expiration'];
        $this->last_activity = $last_activity;
        $this->last_rotation = $last_rotation;
    }
    
    /**
     * @internal
     * @psalm-internal Snicco\Enterprise\Bundle\Auth\Session\Infrastructure
     */
    public static function fromArrayDataForStorage(string $hashed_token, int $int, array $data) :self
    {
        if ( ! isset($data['__snicco_last_active']) || ! is_int($data['__snicco_last_active'])) {
           $data['__snicco_last_active'] = time();
        }
        
        if ( ! isset($data['__snicco_last_rotated']) || ! is_int($data['__snicco_last_rotated'])) {
            $data['__snicco_last_rotated'] = time();
        }
        
        if ( ! isset($data['expiration']) || ! is_int($data['expiration'])) {
            throw new InvalidArgumentException('Session data is corrupted. Key [expiration] invalid.');
        }
        
        return new self($hashed_token, $int, $data['__snicco_last_active'], $data['__snicco_last_rotated'], $data);
    }
    
    public function hashedToken() :string
    {
        return $this->hashed_token;
    }
    
    public function userId() :int
    {
        return $this->user_id;
    }
    
    public function data() :array
    {
        return $this->data;
    }
    
    public function isFullyAuthenticated() :bool
    {
        return $this->fully_authenticated;
    }
    
    public function lastRotation() :int
    {
        return $this->last_rotation;
    }
    
    public function expiresAt() :int
    {
        return $this->expires_at;
    }
    
    public function lastActivity() :int
    {
        return $this->last_activity;
    }
    
    public function withToken(string $hashed_token) :self
    {
        $new = clone $this;
        $new->hashed_token = $hashed_token;
        return $new;
    }
    
    public function withWeakAuthentication() :self
    {
        $new = clone $this;
        $new->fully_authenticated = false;
        return $new;
    }
    
}
