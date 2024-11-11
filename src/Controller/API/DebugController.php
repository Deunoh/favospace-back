<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    /**
     * @Route("/api/debug", name="debug", methods={"GET"})
     */
    public function debug(Request $request): JsonResponse
    {
        return new JsonResponse([
            'headers' => $request->headers->all(),
            'authorization' => $request->headers->get('Authorization'),
            'server_auth' => $request->server->get('HTTP_AUTHORIZATION'),
            'method' => $request->getMethod(),
            'content_type' => $request->headers->get('Content-Type'),
            'request_uri' => $request->getRequestUri(),
            'time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @Route("/api/test", name="test", methods={"GET"})
     */
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'API is working',
            'status' => 'OK',
            'timestamp' => time()
        ]);
    }
}