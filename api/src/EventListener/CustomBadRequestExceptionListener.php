<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\CustomBadRequestException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener(event: ExceptionEvent::class)]
class CustomBadRequestExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof CustomBadRequestException) {
            return;
        }

        $errors = $exception->getErrors();

        $event->setResponse(new JsonResponse(
            ['errors' => $errors],
            Response::HTTP_BAD_REQUEST
        ));
    }
}
