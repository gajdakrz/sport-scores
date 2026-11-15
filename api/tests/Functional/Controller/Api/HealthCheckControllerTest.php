<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthCheckControllerTest extends WebTestCase
{
    #[Test]
    #[TestDox('Health check reports status OK and the application version')]
    public function healthCheckReportsStatusAndVersion(): void
    {
        $client = self::createClient();

        $client->request('GET', '/api/v1/actuator/healthcheck');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame('OK', $data['status']);
        $this->assertSame(self::getContainer()->getParameter('app.version'), $data['version'] ?? null);
    }
}
