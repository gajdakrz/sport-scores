<?php

declare(strict_types=1);

namespace App\Dto\Response;

/**
 * Class for appliacation response
 */
class ExceptionResponse
{
    private ?string $message = null;
    /**
     * @var array<mixed>
     */
    private ?array $errors = null;

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array<mixed>|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * @param array<mixed>|null $errors
     */
    public function setErrors(?array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Return response object as array
     * @return array<string,string|array<mixed>>
     */
    public function toArray(): array
    {
        $response = [];

        if (!is_null($this->getMessage())) {
            $response['message'] = $this->getMessage();
        }

        if (!is_null($this->getErrors())) {
            $response['errors'] = $this->getErrors();
        }

        return $response;
    }
}
