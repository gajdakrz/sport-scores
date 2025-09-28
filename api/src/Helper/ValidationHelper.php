<?php

declare(strict_types=1);

namespace App\Helper;

use Symfony\Component\Validator\Exception\ValidationFailedException;

class ValidationHelper
{
    /**
     * @var ValidationError[]
     */
    private array $validationErrors = [];

    public function prepareValidationErrors(ValidationFailedException $exception): void
    {
        foreach ($exception->getViolations() as $violation) {
            $validationError = new ValidationError($violation);

            $errorToMerge = $this->getErrorToMerge($validationError);
            if (is_null($errorToMerge)) {
                $this->validationErrors[] = $validationError;
                continue;
            }
            $errorToMerge->mergeErrors($validationError);
        }
    }

    /**
     * @return array{
     *     message: string,
     *     field: string
     * }[]
     */
    public function getValidationErrorsAsArray(): array
    {
        return array_map(static fn($validationError) => $validationError->getErrorAsArray(), $this->validationErrors);
    }

    /**
     * @param ValidationError $error
     * @return ValidationError|null
     */
    private function getErrorToMerge(ValidationError $error): ?ValidationError
    {
        foreach ($this->validationErrors as $validationError) {
            if ($validationError->isErrorToMerge($error)) {
                return $validationError;
            }
        }

        return null;
    }
}
