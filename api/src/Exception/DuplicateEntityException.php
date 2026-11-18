<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * The request is valid, but a record with the same unique key already exists (409 Conflict).
 * Same JSON shape as validation errors: { "errors": [{ "message", "field" }] }.
 */
class DuplicateEntityException extends CustomBadRequestException
{
    public const string DEFAULT_MESSAGE = 'Record already exists.';

    /**
     * @param array<int,array<string,string>> $errors
     */
    public function __construct(array $errors = [['message' => self::DEFAULT_MESSAGE, 'field' => '']])
    {
        parent::__construct($errors, Response::HTTP_CONFLICT);
    }
}
