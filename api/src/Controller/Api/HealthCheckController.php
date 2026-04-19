<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag('actuator')]
class HealthCheckController extends BaseController
{
    #[OA\Get(
        operationId: "getHealthStatus",
        summary: "Get health status",
    )]
    #[OA\Response(
        response: 200,
        description: "Successful operation",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: "status",
                    type: "string",
                    example: "OK"
                )
            ],
            type: "object"
        )
    )]
    #[Route('/api/v1/actuator/healthcheck', name: 'healthcheck', methods: 'GET')]
    public function health(): JsonResponse
    {
        return $this->json(['status' => JsonResponse::$statusTexts[JsonResponse::HTTP_OK]]);
    }
}
