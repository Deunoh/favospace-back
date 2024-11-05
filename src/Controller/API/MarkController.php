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
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    #[Route('/{id}/delete', name:'delete', methods:['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Mark $mark, EntityManagerInterface $entityManager): JsonResponse
    {
        // est ce que le mark appartient bien à un espace de l'utilisateur ?
        if($mark->getSpace()->getUser() !== $this->getUser()){
            return $this->json(
                ['message' => 'Vous n\'êtes pas autorisé à supprimer ce favori'],
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $entityManager->remove($mark);
            $entityManager->flush();
    
            return $this->json(
                ['message' => 'Favori supprimé avec succès'],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->json(
                ['message' => 'Erreur lors de la suppression du favori'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    #[Route('/{id}/edit', name:'edit', methods:['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Mark $mark,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        if ($mark->getSpace()->getUser() !== $this->getUser()) {
            return $this->json(
                ['message' => 'Vous n\'êtes pas autorisé à modifier ce favoris'],
                Response::HTTP_FORBIDDEN
            );
        }

        try{
            $updatedMark = $serializer->deserialize(
                $request->getContent(),
                Mark::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $mark]
            );

            $errors = $validator->validate($updatedMark);
            if (count($errors) > 0){
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $entityManager->flush();

            return $this->json(
                $updatedMark, 
                Response::HTTP_OK,
                [],
                ['groups' => 'space_marks']
            );
        } catch (\Exception $e) {
            return $this->json(
                ['message' => 'Erreur lors de la modification du favoris'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
