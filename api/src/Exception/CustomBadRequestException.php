<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomBadRequestException extends HttpException
{
    /**
     * @var array<int,array<string,string>> $errors
     */
    private array $errors;

    /**
     * @param array<int,array<string,string>> $errors
     */
    public function __construct(
        array $errors,
        int $code = Response::HTTP_BAD_REQUEST,
    ) {
        $this->errors = $errors;
        $message = 'Validation errors in your request';
        parent::__construct($code, $message);
    }

    /**
     * @return array<int,array<string,string>> $errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
