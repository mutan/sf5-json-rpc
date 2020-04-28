<?php

namespace App\Controller;

use App\Service\Api\ApiService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1")
 */
class ApiController extends AbstractController
{
    /**
     * @Route("/", name="api_v1")
     * @param Request $request
     * @param LoggerInterface $logger
     * @param ApiService $apiService
     * @return JsonResponse
     */
    public function jsonRpc(Request $request, LoggerInterface $logger, ApiService $apiService): JsonResponse
    {
        return $apiService->handleRequest($request, $logger);
    }
}
