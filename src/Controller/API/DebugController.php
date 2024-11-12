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
      $allEnv = [];
      foreach ($_SERVER as $key => $value) {
          if (str_starts_with($key, 'HTTP_')) {
              $allEnv[$key] = $value;
          }
      }
  
      return new JsonResponse([
          'headers' => $request->headers->all(),
          'authorization' => $request->headers->get('Authorization'),
          'server_auth' => $request->server->get('HTTP_AUTHORIZATION'),
          'all_headers' => getallheaders(),
          'all_server' => $request->server->all(),
          'env_vars' => $allEnv,
          'raw_auth' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
          'method' => $request->getMethod(),
          'content_type' => $request->headers->get('Content-Type'),
          'request_uri' => $request->getRequestUri(),
          'time' => date('Y-m-d H:i:s'),
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