<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new Email())
            ->from('contact.favospace@gmail.com')
            ->to($user->getEmail())
            ->subject(sprintf('Bienvenue sur Favospace internaute Numéro %d  !', $user->getId()))
            ->html(sprintf(
                '<h1>Bienvenue dans l\'espace, Internaute #%d !</h1>
                <p>Nous sommes ravis de vous accueillir dans Favospace, votre espace personnel pour sauvegarder et organiser vos liens favoris.</p>
                <p>Explorez les fonctionnalités de notre plateforme et commencez à construire votre univers de favoris !</p>
                <p>À bientôt dans les étoiles,</p>
                <p>Denovann</p>',
                $user->getId()
            ));

        $this->mailer->send($email);
    }
}
