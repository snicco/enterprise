<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Application;

use Snicco\Enterprise\AuthBundle\Session\Application\DestroyAllSessions\DestroyAllSessions;
use Snicco\Enterprise\AuthBundle\Session\Application\RemoveExpiredSessions\RemoveExpiredSessions;
use Snicco\Enterprise\AuthBundle\Session\Domain\SessionManager;

final class SessionCommandHandler
{
    private SessionManager $session_manager;

    public function __construct(SessionManager $session_repository)
    {
        $this->session_manager = $session_repository;
    }

    public function removeExpiredSessions(RemoveExpiredSessions $command): void
    {
        $this->session_manager->gc();
    }

    public function destroyAllSessions(DestroyAllSessions $command): void
    {
        $this->session_manager->destroyAllSessionsForAllUsers();
    }
}
