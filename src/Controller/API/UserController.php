<?php

namespace App\Controller\API;

use App\Service\BookmarkExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    /**
     * Exporte tous les favoris de l'utilisateur au format compatible Chrome
     */
    #[Route('/user/export-bookmarks', name: 'app_user_export_bookmarks', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportBookmarks(BookmarkExportService $bookmarkExportService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Générer le fichier HTML
        $bookmarkHtml = $bookmarkExportService->generateBookmarkFile($user);

        // Créer un nom de fichier avec timestamp
        $filename = sprintf(
            'favospace_bookmarks_%s_%s.html',
            $user->getName(),
            date('Y-m-d_H-i-s')
        );

        // Retourner le fichier en téléchargement
        $response = new Response($bookmarkHtml);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
        
        return $response;
    }
}
