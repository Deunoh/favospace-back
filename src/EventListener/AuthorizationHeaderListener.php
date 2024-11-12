<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthorizationHeaderListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        if ($authHeader = $request->headers->get('Authorization')) {
            $request->server->set('HTTP_AUTHORIZATION', $authHeader);
        }
    }
}