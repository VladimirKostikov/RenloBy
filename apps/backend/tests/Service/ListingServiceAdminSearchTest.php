<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Common\PaginatedResponse;
use App\Dto\Listing\ListingSearchRequest;
use App\Entity\Listing;
use App\Enum\ListingStatus;
use App\Repository\ListingRepository;
use App\Service\AuthService;
use App\Service\ListingService;
use App\Service\LocationTextResolver;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

final class ListingServiceAdminSearchTest extends TestCase
{
    public function testSearchAdminForcesIncludeNonPublished(): void
    {
        $repository = $this->createMock(ListingRepository::class);
        $repository
            ->expects(self::once())
            ->method('search')
            ->with(self::callback(static function (ListingSearchRequest $request): bool {
                return $request->includeNonPublished === true
                    && $request->status === null
                    && $request->page === 2
                    && $request->limit === 50;
            }))
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 2,
                'limit' => 50,
            ]);

        $service = new ListingService(
            $repository,
            $this->createMock(LocationTextResolver::class),
            $this->createMock(AuthService::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(UserNotificationService::class),
        );

        $result = $service->searchAdmin(new ListingSearchRequest(
            page: 2,
            limit: 50,
        ));

        self::assertInstanceOf(PaginatedResponse::class, $result);
        self::assertSame(0, $result->total);
    }

    public function testGetAdminReturnsDraftWithout404(): void
    {
        $listing = (new Listing())
            ->setStatus(ListingStatus::Draft)
            ->setPrice(100)
            ->setPricePerSqm(10)
            ->setRooms(1)
            ->setArea(40)
            ->setFloor(1)
            ->setTotalFloors(5)
            ->setAddress('Test')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setImages([]);

        $reflection = new \ReflectionProperty(Listing::class, 'id');
        $reflection->setValue($listing, 15);

        $repository = $this->createMock(ListingRepository::class);
        $repository->method('find')->with(15)->willReturn($listing);

        $service = new ListingService(
            $repository,
            $this->createMock(LocationTextResolver::class),
            $this->createMock(AuthService::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(UserNotificationService::class),
        );

        $response = $service->getAdmin(15);

        self::assertSame(15, $response->id);
        self::assertSame(ListingStatus::Draft, $response->status);
    }
}
