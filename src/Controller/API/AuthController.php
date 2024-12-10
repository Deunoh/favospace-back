<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $hasher, 
        EntityManagerInterface $em, 
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EmailService $emailService
    ): JsonResponse 
    {
        try {
            $user = $serializer->deserialize($request->getContent(), User::class, 'json');

            // Validation des Assert 
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $errorsArray = [];
                foreach ($errors as $error) {
                    $errorsArray[$error->getPropertyPath()][] = $error->getMessage();
                }
                return $this->json([
                    'status' => 'error',
                    'errors' => $errorsArray
                ], 400);
            }
            // Hash du mot de passe
            $hashedPassword = $hasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_USER']);
            
            $em->persist($user);
            $em->flush();

            // Envoi du mail de bienvenue
            $emailService->sendWelcomeEmail($user);
            
            return $this->json([
              'status' => 'success',
              'message' => 'Utilisateur enrengistré !'
          ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la création: ' . $e->getMessage()
            ], 400);
        }
    }
    #[Route('/verify-user', methods: ['GET'])]
    public function verifyUser(): JsonResponse
    {
        /** @var User|null */
        $user = $this->getUser();
    
        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Non authentifié'
            ], 401);
        }
    
        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]
        ]);
    }
    #[Route('/delete-account', methods: ['DELETE'])]
    public function deleteAccount(EntityManagerInterface $em ): JsonResponse 
    {
        try {
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }
    
            $em->remove($user);
            $em->flush();
    
            return $this->json([
                'status' => 'success',
                'message' => 'Compte supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 400);
        }
    
    }

    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, MailerInterface $mailer, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $email = json_decode($request->getContent(), true);
        // $email = $serializer->deserialize($request->getContent(), User::class, 'json');                                                               
        // Je verifie si l'utilisateur existe, ici je le cherche grace à l'adresse mail renseignée
        $user = $userRepository->findOneBy(['email' => $email]);
        // dd($user)

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable.'], 404);
        }

        // Je génére un token de reinitialisation 
        $resetToken = bin2hex(random_bytes(32));
        $user->setResetToken($resetToken);
        $user->setTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $em->flush();

        // J'envoi le mail avec le lien de reinitalisation
        // https://www.php.net/manual/fr/function.sprintf.php
        $resetLink = sprintf('https://favospace.fr/reset-password/%s', $resetToken); 
        $email = (new Email())
            ->from('contact.favospace@gmail.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html(sprintf('<p>Cliquez sur ce lien pour réinitialiser votre mot de passe : <a href="%s">Réinitialiser</a></p>', $resetLink));

        $mailer->send($email);

        return new JsonResponse(['message' => 'Un email de réinitialisation a été envoyé.']);
    }

    #[Route('/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Décoder les données JSON de la requête
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        // Il faut le token ET le mot de passe
        if (!$token || !$newPassword) {
            return new JsonResponse(['message' => 'Une erreur s\est produite, veuillez réessayer'], 400);
        }

        $user = $userRepository->findOneBy(['resetToken' => $token]);
        // dd($user);

        // Token toujours valide ?
        if (!$user || $user->getTokenExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse(['message' => 'Le lien est expiré, veuillez réessayer.'], 400);
        }

        // Hasher le nouveau mot de passe, voir pour les verifications
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        // reset
        $user->setResetToken(null);
        $user->setTokenExpiresAt(null);

        $em->flush();

        return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}