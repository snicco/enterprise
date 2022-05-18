<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

final class SessionWasRotated implements Event, ExposeToWP
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    private string $new_token_raw;
    
    private string $new_token_hashed;
    
    private int $user_id;
    
    public function __construct(int $user_id, string $new_token_raw, string $new_token_hashed)
    {
        $this->user_id = $user_id;
        $this->new_token_raw = $new_token_raw;
        $this->new_token_hashed = $new_token_hashed;
    }
    
    public function newTokenRaw() :string
    {
        return $this->new_token_raw;
    }
    
    public function newTokenHashed() :string
    {
        return $this->new_token_hashed;
    }
    
    public function userId() :int
    {
        return $this->user_id;
    }
    
}
