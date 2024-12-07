<?php

namespace App\Controller\API;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        ValidatorInterface $validator
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
}