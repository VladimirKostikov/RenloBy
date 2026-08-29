<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Listing;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Repository\ListingRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ListingContactEventApiTest extends WebTestCase
{
    public function testAnonymousCanRecordContactOpen(): void
    {
        $client = static::createClient();
        $listingId = $this->findPublishedListingId();
        if ($listingId === null) {
            self::markTestSkipped('No published listing in test database');
        }

        $client->request(
            'POST',
            '/api/listings/' . $listingId . '/events/contact',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => 'contact'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok'] ?? false);
    }

    private function findPublishedListingId(): ?int
    {
        /** @var ListingRepository $repo */
        $repo = static::getContainer()->get(ListingRepository::class);
        /** @var list<Listing> $items */
        $items = $repo->findBy(
            ['status' => ListingStatus::Published, 'dealType' => DealType::Sale, 'listingType' => ListingType::Apartment],
            ['id' => 'ASC'],
            1,
        );

        return $items[0]?->getId();
    }
}
