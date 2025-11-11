<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CountryControllerTest extends WebTestCase
{
    private const string USER_EMAIL_TEST = 'test@example.com';

    public function testIndex(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_EMAIL_TEST]);
        $this->assertNotNull($user);
        $client->loginUser($user);
        $client->request('GET', '/countries');
        $this->assertResponseIsSuccessful();
    }
}
