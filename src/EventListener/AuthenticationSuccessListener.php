<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Listener pour personnaliser la réponse lors d'une authentification JWT réussie.
 *
 * Ce service écoute l'événement `lexik_jwt_authentication.on_authentication_success` 
 * et enrichit la réponse avec des informations supplémentaires sur l'utilisateur. Sinon seul le token 
 * JWT est renvoyé
 * 
 * Configuration dans services.yaml :
 * ```yaml
 * app.authentication_success_listener:
 *     class: App\EventListener\AuthenticationSuccessListener
 *     tags:
 *         - { name: kernel.event_listener, 
 *             event: lexik_jwt_authentication.on_authentication_success, 
 *             method: onAuthenticationSuccessResponse }
 * ```
 */
class AuthenticationSuccessListener
{
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data['user'] = [
            'name' => $user->getName(),  
            'email' => $user->getEmail(),
            'id' => $user->getId(),
           
        ];

        $event->setData($data);
    }
}
