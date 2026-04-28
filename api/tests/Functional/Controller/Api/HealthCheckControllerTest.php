<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthCheckControllerTest extends WebTestCase
{
    #[Test]
    #[TestDox('Health check endpoint returns 200 with status OK')]
    public function healthCheckReturnsOk(): void
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
    }
}
