<?php

namespace App\EventListener;

use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\ActivityLogger;

class LogoutListener
{
    private ActivityLogger $activityLogger;
    private TokenStorageInterface $tokenStorage;

    public function __construct(ActivityLogger $activityLogger, TokenStorageInterface $tokenStorage)
    {
        $this->activityLogger = $activityLogger;
        $this->tokenStorage = $tokenStorage;
    }

    public function onLogout(LogoutEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return; 
        }

        $user = $token->getUser();
        if ($user && is_object($user)) {
            $this->activityLogger->logLogout($user);
        }
    }
}
