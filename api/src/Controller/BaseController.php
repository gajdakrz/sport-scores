<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\CustomBadRequestException;
use App\Exception\DuplicateEntityException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

abstract class BaseController extends AbstractController
{
    /**
     * 409 when the only problem is a duplicate (UniqueEntity runs last in each entity's GroupSequence,
     * so it is reported only for otherwise valid data), 400 for any other validation error.
     */
    protected function throwFormErrors(FormInterface $form): never
    {
        $errors = [];
        $onlyDuplicates = true;
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $errors[] = [
                'message' => $error->getMessage(),
                'field'   => $error->getOrigin()?->getName() ?? '',
            ];
            $onlyDuplicates = $onlyDuplicates && $this->isDuplicateError($error);
        }

        if ($errors !== [] && $onlyDuplicates) {
            throw new DuplicateEntityException($errors);
        }

        throw new CustomBadRequestException($errors);
    }

    private function isDuplicateError(FormError $error): bool
    {
        $cause = $error->getCause();

        return $cause instanceof ConstraintViolationInterface && $cause->getConstraint() instanceof UniqueEntity;
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
