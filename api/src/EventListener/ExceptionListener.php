<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Dto\Response\ExceptionResponse;
use App\Exception\CustomBadRequestException;
use App\Helper\ValidationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

readonly class ExceptionListener
{
    public function __construct(private ValidationHelper $validationHelper)
    {
    }

    /**
     * Catch application exceptions and return as json
     * @param ExceptionEvent $event
     * @return void
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof HttpExceptionInterface) {
            $exceptionResponse = new ExceptionResponse();
            $response = new JsonResponse();
            $previous = $exception->getPrevious();
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            if ($exception instanceof CustomBadRequestException) {
                $exceptionResponse->setErrors($exception->getErrors());
            } elseif ($previous instanceof ValidationFailedException) {
                $this->validationHelper->prepareValidationErrors($previous);
                $exceptionResponse->setErrors($this->validationHelper->getValidationErrorsAsArray());
            } else {
                $response->setStatusCode($this->getStatusCode($exception));
                $exceptionResponse->setMessage($exception->getMessage());
            }
            $response->setData($exceptionResponse->toArray());
            $event->setResponse($response);
        }
    }

    private function getStatusCode(HttpExceptionInterface $exception): int
    {
        return 0 !== $exception->getStatusCode() ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
