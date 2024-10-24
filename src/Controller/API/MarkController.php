<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MarkController extends AbstractController
{
    #[Route('/mark', name: 'app_mark')]
    public function index(): Response
    {
        return $this->render('mark/index.html.twig', [
            'controller_name' => 'MarkController',
        ]);
    }
}
