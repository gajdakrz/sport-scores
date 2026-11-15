<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\BaseController;
use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag('actuator')]
class HealthCheckController extends BaseController
{
    public function __construct(
        #[Autowire('%app.version%')]
        private readonly string $appVersion,
    ) {
    }

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
                ),
                new OA\Property(
                    property: "version",
                    type: "string",
                    example: "1.6.0"
                ),
            ],
            type: "object"
        )
    )]
    #[Route('/api/v1/actuator/healthcheck', name: 'healthcheck', methods: 'GET')]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => JsonResponse::$statusTexts[Response::HTTP_OK],
            'version' => $this->appVersion,
        ]);
    }
}
