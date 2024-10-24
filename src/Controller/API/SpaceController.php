<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SpaceController extends AbstractController
{
    #[Route('/space', name: 'app_space')]
    public function index(): Response
    {
        return $this->render('space/index.html.twig', [
            'controller_name' => 'SpaceController',
        ]);
    }
}
