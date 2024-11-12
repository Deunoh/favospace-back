<?php

namespace App\Controller\API;

use App\Entity\Mark;
use App\Entity\Space;
use App\Repository\SpaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/space', name: 'app_api_space_', format: 'json')]
class SpaceController extends AbstractController
{
    #[Route('/browse', name: 'browse', methods: ['GET'])]
    // #[IsGranted('ROLE_USER')]  // Pour s'assuré que l'utilisateur est connecté (sécu)
    public function browse(SpaceRepository $repository): JsonResponse
    {
        $spaces = $repository->findSpaceByUser($this->getUser());
        return $this->json($spaces, Response::HTTP_OK, [], ['groups' => 'space_list']);
    }
    // #[Route('/browse', name: 'browse', methods: ['GET'])]
    // public function browse(Request $request): JsonResponse
    // {
    //     // Récupérer et propager le token
    //     $apacheHeaders = apache_request_headers();
    //     if (isset($apacheHeaders['Authorization'])) {
    //         $auth = $apacheHeaders['Authorization'];
    //         $_SERVER['HTTP_AUTHORIZATION'] = $auth;
    //         $request->headers->set('Authorization', $auth);
    //     }
    
    //     // Debug
    //     $token = $request->headers->get('Authorization');
    //     $serverToken = $request->server->get('HTTP_AUTHORIZATION');
    
    //     // Debug après propagation
    //     $debug = [
    //         'token_from_headers' => $token,
    //         'token_from_server' => $serverToken,
    //         'apache_headers' => $apacheHeaders,
    //         'all_headers' => $request->headers->all(),
    //         'method' => $request->getMethod(),
    //         'server_vars' => array_filter($_SERVER, function($key) {
    //             return strpos($key, 'HTTP_') === 0 || strpos($key, 'AUTH') !== false;
    //         }, ARRAY_FILTER_USE_KEY)
    //     ];
    
    //     // Pour tester si le token est valide
    //     try {
    //         $tokenParts = explode('.', str_replace('Bearer ', '', $token));
    //         $payload = json_decode(base64_decode($tokenParts[1]), true);
    //         $debug['token_payload'] = $payload;
    //     } catch (\Exception $e) {
    //         $debug['token_error'] = $e->getMessage();
    //     }
    
    //     return new JsonResponse(['debug' => $debug]);
    // }

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

        // Ajouter le token de partage

        $newSpace->setShareToken(bin2hex(random_bytes(32)));
    
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
    #[Route('/{id}/edit', name:'edit', methods:['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Space $space,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse 
    {
    // Vérifier si l'espace appartient à l'utilisateur connecté
    if ($space->getUser() !== $this->getUser()) {
        return $this->json(
            ['message' => 'Vous n\'êtes pas autorisé à modifier cet espace'],
            Response::HTTP_FORBIDDEN
        );
    }

    try {
        // Désérialiser et mettre à jour l'espace existant, ne pas écraser l'espace existant sinon perte de l'utilisateur associé
        $updatedSpace = $serializer->deserialize(
            $request->getContent(),
            Space::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $space]
        );

        $errors = $validator->validate($updatedSpace);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(
            $updatedSpace,
            Response::HTTP_OK,
            [],
            ['groups' => 'space_list']
        );

    } catch (\Exception $e) {
        return $this->json(
            ['message' => 'Erreur lors de la modification de l\'espace'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}

#[Route('/clone/{token}', name: 'clone', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function cloneSpace(
    string $token,
    SpaceRepository $repository,
    EntityManagerInterface $entityManager
): JsonResponse {
    // Trouver l'espace source par le token
    $sourceSpace = $repository->findOneBy(['shareToken' => $token]);
    
    if (!$sourceSpace) {
        return $this->json(['message' => 'Espace non trouvé'], Response::HTTP_NOT_FOUND);
    }

    // Créer un nouvel espace
    $newSpace = new Space();
    $newSpace->setName($sourceSpace->getName() . ' (' . $sourceSpace->getUser()->getName() . ')');
    $newSpace->setUser($this->getUser());
    $newSpace->setShareToken(bin2hex(random_bytes(32)));

    // Cloner les marks
    foreach ($sourceSpace->getMarks() as $sourceMark) {
        $newMark = new Mark();
        $newMark->setName($sourceMark->getName());
        $newMark->setUrl($sourceMark->getUrl());
        $newMark->setSpace($newSpace);
        $entityManager->persist($newMark);
    }

    $entityManager->persist($newSpace);
    $entityManager->flush();

    return $this->json(
        $newSpace,
        Response::HTTP_CREATED,
        [],
        ['groups' => 'space_list']
    );
}

};
