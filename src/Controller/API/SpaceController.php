<?php

namespace App\Controller\API;

use App\Entity\Space;
use App\Repository\SpaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/space', name: 'app_api_space_', format: 'json')]
class SpaceController extends AbstractController
{
    #[Route('/browse', name: 'browse', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]  // Pour s'assuré que l'utilisateur est connecté (sécu)
    public function browse(SpaceRepository $repository): JsonResponse
    {
        $spaces = $repository->findSpaceByUser($this->getUser());
        return $this->json($spaces, Response::HTTP_OK, [], ['groups' => 'space_list']);
    }

    // Recuperer les marks associé à l'espace
    #[Route('/{id}/marks', name: 'marks', methods: ['GET'])]
    public function getSpaceMarks(Space $space): JsonResponse
    {
        // Verif si les spaces sont bien ceux de l'utilisateur
        if ($space->getUser() !== $this->getUser()) {
            return $this->json(['message' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }
        
        return $this->json($space, Response::HTTP_OK, [], ['groups' => 'space_marks']);
    }

    #[Route('/add', name:'add', methods:['POST'])]
    #[IsGranted('ROLE_USER')]  
    public function add(
        Request $request, 
        ValidatorInterface $validator, 
        EntityManagerInterface $entityManager, 
        SerializerInterface $serializer  
    ): JsonResponse  
    {
        // Désérialiser les données
        $newSpace = $serializer->deserialize(
            $request->getContent(), 
            Space::class, 
            'json'
        );
    
        // Associer l'espace à l'utilisateur connecté
        $newSpace->setUser($this->getUser());
    
        // Valider
        $errors = $validator->validate($newSpace);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }
    
        // Sauvegarder (tester maintenant)
        $entityManager->persist($newSpace);
        $entityManager->flush();
    
        return $this->json(
            $newSpace, 
            Response::HTTP_CREATED, 
            [], 
            ['groups' => 'space_list']
        );
    }

    #[Route('/{id}/delete', name:'delete', methods:['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Space $space,
        EntityManagerInterface $entityManager
    ): JsonResponse 
    {
        // Vérifier si l'espace appartient à l'utilisateur connecté
        if ($space->getUser() !== $this->getUser()) {
            return $this->json(
                ['message' => 'Vous n\'êtes pas autorisé à supprimer cet espace'],
                Response::HTTP_FORBIDDEN
            );
        }
    
        try {
            // Supprimer l'espace et ses marks associés (grâce à orphanRemoval=true dans l'entité)
            $entityManager->remove($space);
            $entityManager->flush();
    
            return $this->json(
                ['message' => 'Espace supprimé avec succès'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->json(
                ['message' => 'Erreur lors de la suppression de l\'espace'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
};
