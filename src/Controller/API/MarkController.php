<?php

namespace App\Controller\API;

use App\Entity\Mark;
use App\Repository\MarkRepository;
use App\Repository\SpaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/mark', name: 'app_api_mark_', format: 'json')]
class MarkController extends AbstractController
{
    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        SpaceRepository $spaceRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        // 1. Récupérer les données (comment faire avec le serializer ?)
        $data = json_decode($request->getContent(), true);

        // 2. Je verifie les données que je recois
        if (!isset($data['name']) || !isset($data['url']) || !isset($data['spaceId'])) {
            return $this->json([
                'message' => 'Données manquantes'
            ], Response::HTTP_BAD_REQUEST);
        }

        // 3. creation du mark
        $mark = new Mark();
        $mark->setName($data['name']);
        $mark->setUrl($data['url']);

        // 4. Je recupere le space de l'utilisateur en fonction de l'id envoyé
        $space = $spaceRepository->find($data['spaceId']);
        if (!$space || $space->getUser() !== $this->getUser()) {
            return $this->json([
                'message' => 'Espace non trouvé ou non autorisé'
            ], Response::HTTP_FORBIDDEN);
        }

        // 5. J'associe le mark à l'espace
        $mark->setSpace($space);

        // 6. Je valide les données de l'entité
        $errors = $validator->validate($mark);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        // 7. Sauvegarde en bdd
        $entityManager->persist($mark);
        $entityManager->flush();

        // 8. Je retourne le mark créer (peut être renvoyé un msg)
        return $this->json($mark, Response::HTTP_CREATED, [], ['groups' => 'space_marks']);
    }
}
