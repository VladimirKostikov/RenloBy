<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaUploadControllerTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@renlo.local';
    private const ADMIN_PASSWORD = 'Admin123!';

    public function testUploadRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/admin/media/upload');

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanUploadImage(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $tmp = tempnam(sys_get_temp_dir(), 'renlo-img-');
        self::assertNotFalse($tmp);
        $path = $tmp . '.jpg';
        rename($tmp, $path);
        file_put_contents($path, base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxISEhUSEhIVFhUVFRUVFRUVFRUWFxUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAbAAACAwEBAQAAAAAAAAAAAAADBAECBQYAB//EADYQAAIBAwMCBAMFBQEAAAAAAAECAwAEEQUSITFBEyJRYXGBBjKRoRQjQrHB0fAVYnLh8SQz/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAIhEAAgICAgIDAQEAAAAAAAAAAAECERIhAzFBUQQiYZHwMv/aAAwDAQACEQMRAD8A9o0rSlKUAFFFFABRRRQAUUUUAFFFFAH/2Q==',
            true,
        ));

        $file = new UploadedFile($path, 'cover.jpg', 'image/jpeg', null, true);
        $client->request('POST', '/admin/media/upload', [], ['file' => $file]);

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('image', $payload['type']);
        self::assertStringStartsWith('/uploads/articles/', $payload['url']);

        $publicPath = static::getContainer()->getParameter('kernel.project_dir') . '/public' . $payload['url'];
        if (is_file($publicPath)) {
            unlink($publicPath);
        }
        @unlink($path);
    }

    private function loginAsAdmin($client): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => self::ADMIN_EMAIL,
                'password' => self::ADMIN_PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
    }
}
