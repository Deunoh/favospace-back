<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthorizationHeaderListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Vérifie si le header Authorization est présent
        if ($authHeader = $request->headers->get('Authorization')) {
            // Force la création de HTTP_AUTHORIZATION
            $request->server->set('HTTP_AUTHORIZATION', $authHeader);
        }
    }
}