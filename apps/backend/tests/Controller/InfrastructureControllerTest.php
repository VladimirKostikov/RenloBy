<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InfrastructureControllerTest extends WebTestCase
{
    public function testRequiresTypes(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/infrastructure/pois?south=53.85&west=27.45&north=53.95&east=27.65&zoom=14',
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testRequiresValidBbox(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/infrastructure/pois?types=shop&south=54&west=27&north=53&east=28',
        );

        self::assertResponseStatusCodeSame(400);
    }
}
