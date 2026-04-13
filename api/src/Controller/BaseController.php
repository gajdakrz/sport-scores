<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends AbstractController
{
    protected function validateCsrfToken(string $action, string $token): ?JsonResponse
    {
        if (!$this->isCsrfTokenValid($action, $token)) {
            return new JsonResponse(
                ['success' => false, 'error' => 'Invalid CSRF token'],
                Response::HTTP_FORBIDDEN
            );
        }

        return null;
    }
}
