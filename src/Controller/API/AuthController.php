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

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $hasher, 
        EntityManagerInterface $em, 
        SerializerInterface $serializer
    ): JsonResponse 
    {
        try {
            $user = $serializer->deserialize($request->getContent(), User::class, 'json');
            
            // Hash du mot de passe
            $hashedPassword = $hasher->hashPassword($user, $user->getPassword());
            // TODO verif data is valid
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_USER']);
            
            $em->persist($user);
            $em->flush();
            
            return $this->json([
                'message' => 'User created successfully'
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error creating user: ' . $e->getMessage()
            ], 400);
        }
    }
}