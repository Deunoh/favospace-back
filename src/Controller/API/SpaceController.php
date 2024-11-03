<?php

namespace App\Controller\API;

use App\Entity\Space;
use App\Repository\SpaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
}
