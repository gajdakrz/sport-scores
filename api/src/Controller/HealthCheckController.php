<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 *  Health check
 *
 *  @OA\Tag(name="HealthCheck")
*/
#[Route('/api/actuator/', name: 'actuator_')]
class HealthCheckController extends AbstractController
{
    /**
     * @return JsonResponse
     *
     * @OA\Get(
     *     summary="Get health status",
     *     operationId="getHealthStatus",
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Successful operation",
     *     @OA\JsonContent(
     *          @OA\Property(type="string", property="status", example="OK")
     *     )
     * )
    */
    #[Route('health', name: 'health', methods: 'GET')]
    public function health(): JsonResponse
    {
        return $this->json(['status' => JsonResponse::$statusTexts[JsonResponse::HTTP_OK]]);
    }
}
