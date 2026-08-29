<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Listing;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\ListingStatus;
use App\Enum\NotificationType;
use App\Repository\UserNotificationRepository;
use App\Service\SellerTelegramService;
use App\Service\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserNotificationServiceTest extends TestCase
{
    public function testNotifyListingStatusChangedPersistsNotification(): void
    {
        $user = (new User())->setEmail('seller@example.com');
        $userId = new \ReflectionProperty(User::class, 'id');
        $userId->setValue($user, 3);

        $listing = (new Listing())
            ->setStatus(ListingStatus::Pending)
            ->setPrice(100)
            ->setPricePerSqm(10)
            ->setRooms(1)
            ->setArea(40)
            ->setFloor(1)
            ->setTotalFloors(5)
            ->setAddress('ул. Тест, 10')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setImages([])
            ->setUser($user)
            ->setIsTest(false);

        $listingId = new \ReflectionProperty(Listing::class, 'id');
        $listingId->setValue($listing, 55);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (object $entity) use ($user): bool {
                if (!$entity instanceof UserNotification) {
                    return false;
                }

                return $entity->getUser() === $user
                    && $entity->getType() === NotificationType::ListingStatusChanged
                    && ($entity->getPayload()['listingId'] ?? null) === 55
                    && ($entity->getPayload()['status'] ?? null) === 'published'
                    && ($entity->getPayload()['previousStatus'] ?? null) === 'pending'
                    && ($entity->getPayload()['address'] ?? null) === 'ул. Тест, 10'
                    && $entity->isTest() === false;
            }));

        $telegram = $this->createMock(SellerTelegramService::class);
        $telegram->expects(self::once())->method('notifyUser');

        $service = new UserNotificationService(
            $this->createMock(UserNotificationRepository::class),
            $entityManager,
            $telegram,
        );

        $service->notifyListingStatusChanged($listing, ListingStatus::Pending, ListingStatus::Published);
    }

    public function testNotifySkipsWhenStatusUnchangedOrNoUser(): void
    {
        $listing = (new Listing())
            ->setStatus(ListingStatus::Pending)
            ->setPrice(100)
            ->setPricePerSqm(10)
            ->setRooms(1)
            ->setArea(40)
            ->setFloor(1)
            ->setTotalFloors(5)
            ->setAddress('No user')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setImages([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $telegram = $this->createMock(SellerTelegramService::class);
        $telegram->expects(self::never())->method('notifyUser');

        $service = new UserNotificationService(
            $this->createMock(UserNotificationRepository::class),
            $entityManager,
            $telegram,
        );

        $service->notifyListingStatusChanged($listing, ListingStatus::Pending, ListingStatus::Pending);
        $service->notifyListingStatusChanged($listing, ListingStatus::Pending, ListingStatus::Published);
    }
}
