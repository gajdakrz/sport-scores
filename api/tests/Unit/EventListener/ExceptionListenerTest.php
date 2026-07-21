<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ExceptionListener;
use App\Helper\ValidationHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ExceptionListenerTest extends TestCase
{
    #[Test]
    #[TestDox('Masks the exception message and logs it for 5xx errors')]
    public function masksMessageAndLogsForServerErrors(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Doctrine connection failed: password=secret', $this->arrayHasKey('exception'));

        $listener = new ExceptionListener(new ValidationHelper(), $logger);

        $exception = new HttpException(500, 'Doctrine connection failed: password=secret');
        $event = $this->createExceptionEvent($exception);

        $listener($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(500, $response->getStatusCode());

        $data = $this->decodeJson((string) $response->getContent());
        $this->assertSame('Internal server error.', $data['message']);
        $this->assertStringNotContainsString('secret', (string) $response->getContent());
    }

    #[Test]
    #[TestDox('Passes through the exception message unchanged for 4xx errors without logging')]
    public function passesThroughMessageForClientErrorsWithoutLogging(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $listener = new ExceptionListener(new ValidationHelper(), $logger);

        $exception = new HttpException(404, 'Resource not found');
        $event = $this->createExceptionEvent($exception);

        $listener($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());

        $data = $this->decodeJson((string) $response->getContent());
        $this->assertSame('Resource not found', $data['message']);
    }

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/v1/whatever');

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $content): array
    {
        $data = json_decode($content, true);
        $this->assertIsArray($data);

        return $data;
    }
}
