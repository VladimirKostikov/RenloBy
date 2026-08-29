<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    private const REGISTER_EMAIL = 'auth-test-user@renlo.local';
    private const REGISTER_PASSWORD = 'SecurePass1';

    public function testRegisterCreatesUserAndSetsCookie(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => self::REGISTER_EMAIL,
                'password' => self::REGISTER_PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('content-type', 'application/json');

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(self::REGISTER_EMAIL, $payload['email']);
        self::assertContains('ROLE_USER', $payload['roles']);
        $cookies = $client->getResponse()->headers->getCookies();
        self::assertNotEmpty($cookies);
        $authCookie = $cookies[0];
        self::assertSame('RENLO_TOKEN', $authCookie->getName());
        self::assertFalse($authCookie->isSecure());
        self::assertTrue($authCookie->isHttpOnly());
    }

    public function testRegisterDuplicateEmailReturnsConflict(): void
    {
        $client = static::createClient();
        $body = json_encode([
            'email' => 'duplicate-user@renlo.local',
            'password' => self::REGISTER_PASSWORD,
        ], JSON_THROW_ON_ERROR);

        $client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(409);

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('auth.email_exists', $payload['error']);
    }

    public function testRegisterValidationFailsForShortPassword(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'short-pass@renlo.local',
                'password' => 'short',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('fields', $payload);
        self::assertArrayHasKey('password', $payload['fields']);
    }

    public function testLoginSuccessReturnsUserAndCookie(): void
    {
        $client = static::createClient();
        $email = 'login-success@renlo.local';
        $this->registerUser($client, $email, self::REGISTER_PASSWORD);

        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => self::REGISTER_PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($email, $payload['email']);
        self::assertNotEmpty($client->getResponse()->headers->getCookies());
    }

    public function testLoginFailureReturnsUnauthorized(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'missing-user@renlo.local',
                'password' => 'WrongPass1',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('auth.invalid_credentials', $payload['error']);
    }

    public function testMeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsCurrentUser(): void
    {
        $client = static::createClient();
        $email = 'me-endpoint@renlo.local';
        $this->registerUser($client, $email, self::REGISTER_PASSWORD);

        $client->request('GET', '/api/auth/me');
        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($email, $payload['email']);
        self::assertArrayHasKey('phone', $payload);
        self::assertArrayHasKey('photo', $payload);
        self::assertArrayHasKey('instagram', $payload);
        self::assertArrayHasKey('telegram', $payload);
        self::assertArrayHasKey('whatsapp', $payload);
        self::assertArrayHasKey('viber', $payload);
    }

    public function testUpdateProfileSavesContactsAndSocials(): void
    {
        $client = static::createClient();
        $email = 'profile-update@renlo.local';
        $this->registerUser($client, $email, self::REGISTER_PASSWORD);

        $client->request(
            'PATCH',
            '/api/auth/me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'lastName' => 'Петров',
                'firstName' => 'Иван',
                'phone' => '+375291112233',
                'instagram' => '@ivan',
                'telegram' => '@ivan_tg',
                'whatsapp' => '+375291112233',
                'viber' => '+375291112233',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Петров Иван', $payload['name']);
        self::assertSame('Петров', $payload['lastName']);
        self::assertSame('Иван', $payload['firstName']);
        self::assertSame('+375291112233', $payload['phone']);
        self::assertSame('@ivan', $payload['instagram']);
        self::assertSame('@ivan_tg', $payload['telegram']);
        self::assertSame('+375291112233', $payload['whatsapp']);
        self::assertSame('+375291112233', $payload['viber']);
    }

    public function testUpdateProfileRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request(
            'PATCH',
            '/api/auth/me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['firstName' => 'No Auth'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUpdateProfileRejectsInvalidPhotoUrl(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'bad-photo@renlo.local', self::REGISTER_PASSWORD);

        $client->request(
            'PATCH',
            '/api/auth/me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'photo' => 'javascript:alert(1)',
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testUploadProfilePhoto(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'photo-upload@renlo.local', self::REGISTER_PASSWORD);

        $tmp = tempnam(sys_get_temp_dir(), 'avatar');
        self::assertNotFalse($tmp);
        $imagePath = $tmp . '.jpg';
        rename($tmp, $imagePath);
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGcP//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z',
            true,
        );
        self::assertNotFalse($jpeg);
        file_put_contents($imagePath, $jpeg);

        $client->request(
            'POST',
            '/api/auth/me/photo',
            [],
            [
                'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    $imagePath,
                    'avatar.jpg',
                    'image/jpeg',
                    null,
                    true,
                ),
            ],
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsString($payload['photo'] ?? null);
        self::assertStringStartsWith('/uploads/avatars/', $payload['photo']);
        @unlink($imagePath);
    }

    public function testLogoutClearsCookie(): void
    {
        $client = static::createClient();
        $this->registerUser($client, 'logout-user@renlo.local', self::REGISTER_PASSWORD);

        $client->request('POST', '/api/auth/logout');
        self::assertResponseStatusCodeSame(204);
    }

    private function registerUser($client, string $email, string $password): void
    {
        $uniqueEmail = str_replace('@', '+' . uniqid('', true) . '@', $email);

        $client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $uniqueEmail,
                'password' => $password,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
    }
}
