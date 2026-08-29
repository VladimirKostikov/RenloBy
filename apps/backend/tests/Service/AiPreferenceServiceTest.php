<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Ai\DeepSeekChatClient;
use App\Dto\AiAssistant\CreateAiPreferenceRequest;
use App\Dto\Collection\CollectionOwner;
use App\Dto\Listing\ListingResponse;
use App\Entity\AiPreference;
use App\Entity\Listing;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Factory\AiPreferenceFactory;
use App\Repository\AiPreferenceRepository;
use App\Repository\ListingRepository;
use App\Service\AiPreferenceService;
use App\Service\CurrencyConverter;
use App\Service\ListingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AiPreferenceServiceTest extends TestCase
{
    public function testCreatePersistsPreferenceWithRuleBasedSummaryWhenAiUnavailable(): void
    {
        $listing = $this->createStub(Listing::class);
        $listing->method('getId')->willReturn(11);
        $listing->method('isAiGoodPrice')->willReturn(true);
        $listing->method('getMetroMinutes')->willReturn(8);
        $listing->method('isVerified')->willReturn(true);

        $listingRepository = $this->createStub(ListingRepository::class);
        $listingRepository->method('search')->willReturn([
            'items' => [$listing],
            'total' => 1,
            'page' => 1,
            'limit' => 40,
        ]);

        $listingService = $this->createStub(ListingService::class);
        $listingService->method('get')->willReturn($this->createListingResponse(11));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(AiPreference::class));
        $entityManager->expects(self::once())->method('flush');

        $deepSeek = new DeepSeekChatClient(
            $this->createStub(HttpClientInterface::class),
            new NullLogger(),
            '',
        );

        $service = new AiPreferenceService(
            $this->createStub(AiPreferenceRepository::class),
            $listingRepository,
            $listingService,
            $deepSeek,
            $entityManager,
            new AiPreferenceFactory(),
            new CurrencyConverter(3.27, 93.0),
        );

        $response = $service->create(
            new CollectionOwner(null, 'guest-hash-1'),
            new CreateAiPreferenceRequest([
                'dealType' => 'rent',
                'budgetMin' => 300,
                'budgetMax' => 600,
                'rooms' => 2,
                'priorities' => ['fromOwner', 'aiGoodPrice'],
            ]),
        );

        self::assertNull($response->userId);
        self::assertSame('guest-hash-1', $response->guestSessionHash);
        self::assertSame([11], $response->recommendedListingIds);
        self::assertNotEmpty($response->summary);
        self::assertNotEmpty($response->highlights);
        self::assertCount(1, $response->listings);
    }

    private function createListingResponse(int $id): ListingResponse
    {
        return new ListingResponse(
            id: $id,
            dealType: DealType::Rent,
            listingType: ListingType::Apartment,
            status: ListingStatus::Published,
            price: 400,
            pricePerSqm: 10,
            rooms: 2,
            area: 40.0,
            floor: 3,
            totalFloors: 9,
            address: 'Test',
            latitude: 53.9,
            longitude: 27.5,
            metroMinutes: 8,
            verified: true,
            aiGoodPrice: true,
            rentTerm: null,
            hasDeposit: false,
            utilitiesIncluded: false,
            noCommission: false,
            fromOwner: true,
            hasRenovation: false,
            priceNegotiable: false,
            views: 0,
            images: [],
            metaTitle: null,
            metaDescription: null,
            metaKeywords: null,
            publishedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            userId: 1,
            cityId: 1,
            districtId: 1,
            metroStationId: null,
            cityName: 'Minsk',
            districtName: 'Central',
            metroStationName: null,
            isTest: false,
        );
    }
}
