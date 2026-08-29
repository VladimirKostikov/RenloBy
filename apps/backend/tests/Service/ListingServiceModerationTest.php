<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Listing\UpdateListingRequest;
use App\Entity\Listing;
use App\Enum\ListingStatus;
use App\Message\ListingCreatedMessage;
use App\Repository\ListingRepository;
use App\Service\AuthService;
use App\Service\ListingService;
use App\Service\LocationTextResolver;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ListingServiceModerationTest extends TestCase
{
    public function testUpdatePublishesPendingListingAndDispatchesMessage(): void
    {
        $listing = (new Listing())
            ->setStatus(ListingStatus::Pending)
            ->setPrice(100)
            ->setPricePerSqm(10)
            ->setRooms(1)
            ->setArea(40)
            ->setFloor(1)
            ->setTotalFloors(5)
            ->setAddress('Test moderation')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setImages([]);

        $reflection = new \ReflectionProperty(Listing::class, 'id');
        $reflection->setValue($listing, 42);

        $beforePublish = $listing->getPublishedAt();

        $repository = $this->createMock(ListingRepository::class);
        $repository->method('find')->with(42)->willReturn($listing);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $message): bool {
                return $message instanceof ListingCreatedMessage
                    && $message->listingId === 42;
            }))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));

        $notifications = $this->createMock(UserNotificationService::class);
        $notifications
            ->expects(self::once())
            ->method('notifyListingStatusChanged')
            ->with($listing, ListingStatus::Pending, ListingStatus::Published);

        $service = new ListingService(
            $repository,
            $this->createMock(LocationTextResolver::class),
            $this->createMock(AuthService::class),
            $entityManager,
            $messageBus,
            $notifications,
        );

        $response = $service->update(42, new UpdateListingRequest(status: ListingStatus::Published));

        self::assertSame(ListingStatus::Published, $response->status);
        self::assertSame(ListingStatus::Published, $listing->getStatus());
        self::assertGreaterThanOrEqual($beforePublish->getTimestamp(), $listing->getPublishedAt()->getTimestamp());
    }

    public function testUpdateDoesNotRedispatchWhenAlreadyPublished(): void
    {
        $listing = (new Listing())
            ->setStatus(ListingStatus::Published)
            ->setPrice(100)
            ->setPricePerSqm(10)
            ->setRooms(1)
            ->setArea(40)
            ->setFloor(1)
            ->setTotalFloors(5)
            ->setAddress('Already live')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setImages([]);

        $reflection = new \ReflectionProperty(Listing::class, 'id');
        $reflection->setValue($listing, 7);

        $repository = $this->createMock(ListingRepository::class);
        $repository->method('find')->with(7)->willReturn($listing);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $notifications = $this->createMock(UserNotificationService::class);
        $notifications->expects(self::never())->method('notifyListingStatusChanged');

        $service = new ListingService(
            $repository,
            $this->createMock(LocationTextResolver::class),
            $this->createMock(AuthService::class),
            $this->createMock(EntityManagerInterface::class),
            $messageBus,
            $notifications,
        );

        $response = $service->update(7, new UpdateListingRequest(status: ListingStatus::Published));

        self::assertSame(ListingStatus::Published, $response->status);
    }

    public function testUpdateRejectsListingAndSanitizesImages(): void
    {
        $listing = (new Listing())
            ->setStatus(ListingStatus::Pending)
            ->setPrice(100)
            ->setPricePerSqm(10)
            ->setRooms(1)
            ->setArea(40)
            ->setFloor(1)
            ->setTotalFloors(5)
            ->setAddress('Reject me')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setImages(['https://example.com/old.jpg']);

        $reflection = new \ReflectionProperty(Listing::class, 'id');
        $reflection->setValue($listing, 9);

        $repository = $this->createMock(ListingRepository::class);
        $repository->method('find')->with(9)->willReturn($listing);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::never())->method('dispatch');

        $notifications = $this->createMock(UserNotificationService::class);
        $notifications
            ->expects(self::once())
            ->method('notifyListingStatusChanged')
            ->with($listing, ListingStatus::Pending, ListingStatus::Rejected);

        $service = new ListingService(
            $repository,
            $this->createMock(LocationTextResolver::class),
            $this->createMock(AuthService::class),
            $entityManager,
            $messageBus,
            $notifications,
        );

        $response = $service->update(9, new UpdateListingRequest(
            images: [
                '/uploads/listings/2026/07/photo.jpg',
                'javascript:alert(1)',
                'https://cdn.example.com/ok.jpg',
                '../etc/passwd',
            ],
            status: ListingStatus::Rejected,
        ));

        self::assertSame(ListingStatus::Rejected, $response->status);
        self::assertSame(ListingStatus::Rejected, $listing->getStatus());
        self::assertSame(
            ['/uploads/listings/2026/07/photo.jpg', 'https://cdn.example.com/ok.jpg'],
            $listing->getImages(),
        );
        self::assertSame(
            ['/uploads/listings/2026/07/photo.jpg', 'https://cdn.example.com/ok.jpg'],
            $response->images,
        );
    }
}
