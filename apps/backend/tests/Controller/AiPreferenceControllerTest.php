<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AiPreferenceControllerTest extends WebTestCase
{
    public function testGuestCanCreateAndFetchLatestPreference(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/ai-preferences',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'answers' => [
                    'dealType' => 'rent',
                    'budgetMin' => 200,
                    'budgetMax' => 900,
                    'rooms' => 1,
                    'priorities' => ['fromOwner'],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($created);
        self::assertArrayHasKey('id', $created);
        self::assertArrayHasKey('summary', $created);
        self::assertNull($created['userId']);
        self::assertNotEmpty($created['guestSessionHash']);

        $client->request('GET', '/api/ai-preferences/latest');
        self::assertResponseIsSuccessful();
        $latest = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($created['id'], $latest['item']['id'] ?? null);
    }

    public function testCreateRejectsMissingAnswers(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/ai-preferences',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['answers' => []], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }
}
