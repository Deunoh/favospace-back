<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'app_debug_', format: 'json')]
class DebugController extends AbstractController
{
    #[Route('/debug', name: 'debug', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function debug(Request $request): JsonResponse
    {
        // Récupérer tous les headers possibles
        $headers = [];
        foreach (array_keys($_SERVER) as $key) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[$key] = $_SERVER[$key];
            }
        }

        // Get Apache headers
        $apacheHeaders = apache_request_headers();
        
        // Get Authorization header spécifiquement
        $authHeader = null;
        if (isset($apacheHeaders['Authorization'])) {
            $authHeader = $apacheHeaders['Authorization'];
            $_SERVER['HTTP_AUTHORIZATION'] = $authHeader;
            $request->headers->set('Authorization', $authHeader);
        }

        return new JsonResponse([
            'debug_version' => '2.0',
            'apache_headers' => $apacheHeaders,
            'auth_header_direct' => $authHeader,
            'auth_from_server' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            'auth_from_request' => $request->headers->get('Authorization'),
            'all_php_headers' => $headers,
            'server_auth' => $request->server->get('HTTP_AUTHORIZATION'),
            'method' => $request->getMethod(),
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/test', name: 'test', methods: ['GET'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'API is working',
            'status' => 'OK',
            'timestamp' => time()
        ]);
    }
}