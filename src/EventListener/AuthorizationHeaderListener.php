<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class AuthorizationHeaderListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Récupérer le header des différentes sources possibles
        $authHeader = $request->headers->get('Authorization')
            ?? $_SERVER['HTTP_AUTHORIZATION'] 
            ?? apache_request_headers()['Authorization'] 
            ?? null;

        if ($authHeader) {
            // Mettre à jour toutes les sources possibles
            $_SERVER['HTTP_AUTHORIZATION'] = $authHeader;
            $request->server->set('HTTP_AUTHORIZATION', $authHeader);
            $request->headers->set('Authorization', $authHeader);
        }
    }
}