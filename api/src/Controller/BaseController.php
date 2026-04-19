<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\CustomBadRequestException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseController extends AbstractController
{
    protected function throwFormErrors(FormInterface $form): never
    {
        $errors = [];
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $errors[] = [
                'message' => $error->getMessage(),
                'field'   => $error->getOrigin()?->getName() ?? '',
            ];
        }

        throw new CustomBadRequestException($errors);
    }
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
