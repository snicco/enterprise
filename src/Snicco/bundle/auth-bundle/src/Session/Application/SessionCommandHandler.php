<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Application;

use Snicco\Enterprise\Bundle\Auth\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Auth\Session\Application\DestroyAllSessions\DestroyAllSessions;
use Snicco\Enterprise\Bundle\Auth\Session\Application\RemoveExpiredSessions\RemoveExpiredSessions;

final class SessionCommandHandler
{
    private SessionManager $session_manager;
    
    public function __construct(SessionManager $session_repository)
    {
        $this->session_manager = $session_repository;
    }
    
    public function removeExpiredSessions(RemoveExpiredSessions $command) :void
    {
        $this->session_manager->gc();
    }
    
    public function destroyAllSessions(DestroyAllSessions $command) :void
    {
        $this->session_manager->destroyAllSessionsForAllUsers();
    }
}