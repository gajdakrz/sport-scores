<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api;

use App\Tests\Trait\ControllerTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class UserControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private const string ENDPOINT  = '/api/v1/user';
    private const string ROLE_USER  = 'ROLE_USER';
    private const string ROLE_ADMIN = 'ROLE_ADMIN';

    // -------------------------------------------------------------------------
    // Sukces – 201 Created
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns 201 when registering an admin (no prior admin required)')]
    public function registeringAdminReturnsCreated(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'admin_' . uniqid() . '@example.com',
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_ADMIN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    #[Test]
    #[TestDox('Response body contains status "success" and confirmation message on successful registration')]
    public function successfulRegistrationReturnsSuccessBody(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'admin_' . uniqid() . '@example.com',
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_ADMIN,
        ]);

        $data = $this->decodeResponse($client);
        $this->assertSame('success', $data['status']);
        $this->assertSame('Account was created.', $data['message']);
    }

    #[Test]
    #[TestDox('Returns 201 when registering a user after an admin already exists')]
    public function registeringUserAfterAdminReturnsCreated(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'admin_' . uniqid() . '@example.com',
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_ADMIN,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->postJson($client, [
            'email' => 'user_' . uniqid() . '@example.com',
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_USER,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    // -------------------------------------------------------------------------
    // Konflikt – 409
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns 409 when trying to register a user while no admin exists yet')]
    public function registeringUserWithoutAdminReturnsConflict(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'user_' . uniqid() . '@example.com',
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_USER,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    #[Test]
    #[TestDox('Returns 409 when email is already registered')]
    public function duplicateEmailReturnsConflict(): void
    {
        $client = $this->createAuthenticatedClient();
        $email = 'admin_' . uniqid() . '@example.com';

        $this->postJson($client, [
            'email' => $email,
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_ADMIN,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->postJson($client, [
            'email' => $email,
            'plainPassword' => 'OtherPass1!',
            'role' => self::ROLE_ADMIN,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    // -------------------------------------------------------------------------
    // Nieprawidłowy JSON – 400 Bad Request
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns 400 when request body is not valid JSON')]
    public function malformedJsonReturnsBadRequest(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postRaw($client, '{invalid_json}');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    #[TestDox('Error message starts with "Wrong json:" when request body is not valid JSON')]
    public function malformedJsonResponseContainsWrongJsonMessage(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postRaw($client, '{invalid_json}');

        $data = $this->decodeResponse($client);
        $this->assertArrayHasKey('errors', $data);

        $errors = $data['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertIsArray($errors[0]);
        $this->assertArrayHasKey('message', $errors[0]);
        $message = $errors[0]['message'];
        $this->assertIsString($message);
        $this->assertStringStartsWith('Wrong json:', $message);
    }

    #[Test]
    #[TestDox('Returns 400 when request body is completely empty')]
    public function emptyBodyReturnsBadRequest(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postRaw($client, '');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // -------------------------------------------------------------------------
    // Błędy walidacji – 400 Bad Request
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Returns 400 with validation errors when required fields are missing')]
    public function missingFieldsReturnValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, []);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->decodeResponse($client);
        $this->assertArrayHasKey('errors', $data);

        $errors = $data['errors'];
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    #[Test]
    #[TestDox('Each validation error entry contains "message" and "field" keys')]
    public function validationErrorEntriesHaveCorrectStructure(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, []);

        $data = $this->decodeResponse($client);
        $errors = $data['errors'];
        $this->assertIsArray($errors);

        foreach ($errors as $error) {
            $this->assertIsArray($error);
            $this->assertArrayHasKey('message', $error);
            $this->assertArrayHasKey('field', $error);
        }
    }

    #[Test]
    #[TestDox('Returns 400 validation error when email field is missing')]
    public function missingEmailReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_ADMIN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->decodeResponse($client);
        $errors = $data['errors'];
        $this->assertIsArray($errors);

        $fields = array_column($errors, 'field');
        $this->assertContains('email', $fields);
    }

    #[Test]
    #[TestDox('Returns 400 validation error when password field is missing')]
    public function missingPasswordReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'admin_' . uniqid() . '@example.com',
            'role' => self::ROLE_ADMIN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->decodeResponse($client);
        $errors = $data['errors'];
        $this->assertIsArray($errors);

        $fields = array_column($errors, 'field');
        $this->assertContains('plainPassword', $fields);
    }

    #[Test]
    #[TestDox('Returns 400 validation error when email format is invalid')]
    public function invalidEmailFormatReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'not-an-email',
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_ADMIN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->decodeResponse($client);
        $errors = $data['errors'];
        $this->assertIsArray($errors);

        $fields = array_column($errors, 'field');
        $this->assertContains('email', $fields);
    }

    #[Test]
    #[TestDox('Returns 400 validation error when password is shorter than 6 characters')]
    public function tooShortPasswordReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'admin_' . uniqid() . '@example.com',
            'plainPassword' => '123',
            'role' => self::ROLE_ADMIN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->decodeResponse($client);
        $errors = $data['errors'];
        $this->assertIsArray($errors);

        $fields = array_column($errors, 'field');
        $this->assertContains('plainPassword', $fields);
    }

    // -------------------------------------------------------------------------
    // Nagłówki odpowiedzi
    // -------------------------------------------------------------------------

    #[Test]
    #[TestDox('Response Content-Type is application/json for successful registration')]
    public function successResponseHasJsonContentType(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, [
            'email' => 'admin_' . uniqid() . '@example.com',
            'plainPassword' => 'Secret123!',
            'role' => self::ROLE_ADMIN,
        ]);

        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    #[Test]
    #[TestDox('Response Content-Type is application/json for validation error response')]
    public function errorResponseHasJsonContentType(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->postJson($client, []);

        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = self::createClient();
        $client->loginUser($this->getTestUser(), 'api');

        return $client;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function postJson(KernelBrowser $client, array $data): void
    {
        $client->request(
            method: 'POST',
            uri: self::ENDPOINT,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($data) ?: '{}',
        );
    }

    private function postRaw(KernelBrowser $client, string $body): void
    {
        $client->request(
            method: 'POST',
            uri: self::ENDPOINT,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $body,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        return $data;
    }
}
