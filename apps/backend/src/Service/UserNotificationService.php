<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Notification\UnreadCountResponse;
use App\Dto\Notification\UserNotificationResponse;
use App\Entity\Listing;
use App\Entity\ListingRequest;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\ListingStatus;
use App\Enum\NotificationType;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\UserNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserNotificationService
{
    public function __construct(
        private readonly UserNotificationRepository $userNotificationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SellerTelegramService $sellerTelegramService,
    ) {
    }

    /**
     * @return list<UserNotificationResponse>
     */
    public function listForUser(User $user, int $limit = 50): array
    {
        return array_map(
            static fn (UserNotification $notification) => UserNotificationResponse::fromEntity($notification),
            $this->userNotificationRepository->findByUser($user, $limit),
        );
    }

    public function unreadCount(User $user): UnreadCountResponse
    {
        return new UnreadCountResponse($this->userNotificationRepository->countUnreadByUser($user));
    }

    public function markRead(User $user, int $id): UserNotificationResponse
    {
        $notification = $this->ownedNotification($user, $id);
        if (!$notification->isRead()) {
            $notification->setReadAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }

        return UserNotificationResponse::fromEntity($notification);
    }

    public function markAllRead(User $user): UnreadCountResponse
    {
        $this->userNotificationRepository->markAllReadForUser($user);

        return new UnreadCountResponse(0);
    }

    public function notifyListingStatusChanged(
        Listing $listing,
        ListingStatus $previousStatus,
        ListingStatus $newStatus,
    ): void {
        if ($previousStatus === $newStatus) {
            return;
        }

        $user = $listing->getUser();
        if (!$user instanceof User) {
            return;
        }

        $notification = (new UserNotification())
            ->setUser($user)
            ->setType(NotificationType::ListingStatusChanged)
            ->setPayload([
                'listingId' => $listing->getId(),
                'address' => $listing->getAddress(),
                'previousStatus' => $previousStatus->value,
                'status' => $newStatus->value,
            ])
            ->setIsTest($listing->isTest());

        $this->entityManager->persist($notification);

        $address = $listing->getAddress() !== '' ? $listing->getAddress() : ('№' . ($listing->getId() ?? 0));
        $this->sellerTelegramService->notifyUser(
            $user,
            "Renlo: статус объявления изменён\n"
            . $address . "\n"
            . $previousStatus->value . ' → ' . $newStatus->value
        );
    }

    public function notifyListingContactRequestCreated(ListingRequest $request): void
    {
        $listing = $request->getListing();
        if ($listing === null) {
            return;
        }

        $user = $listing->getUser();
        if (!$user instanceof User) {
            return;
        }

        $message = $request->getMessage();
        if (mb_strlen($message) > 200) {
            $message = mb_substr($message, 0, 200);
        }

        $notification = (new UserNotification())
            ->setUser($user)
            ->setType(NotificationType::ListingContactRequestCreated)
            ->setPayload([
                'listingId' => $listing->getId(),
                'address' => $listing->getAddress(),
                'requestId' => $request->getId(),
                'phone' => $request->getPhone(),
                'name' => $request->getName(),
                'message' => $message,
            ])
            ->setIsTest($listing->isTest());

        $this->entityManager->persist($notification);

        $address = $listing->getAddress() !== '' ? $listing->getAddress() : ('№' . ($listing->getId() ?? 0));
        $name = $request->getName() !== '' ? $request->getName() : '-';
        $this->sellerTelegramService->notifyUser(
            $user,
            "Renlo: новая заявка по объявлению\n"
            . $address . "\n"
            . 'Имя: ' . $name . "\n"
            . 'Телефон: ' . $request->getPhone()
        );
    }

    /**
     * @return list<UserNotificationResponse>
     */
    public function listAdmin(): array
    {
        /** @var list<UserNotification> $items */
        $items = $this->userNotificationRepository->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (UserNotification $notification) => UserNotificationResponse::fromEntity($notification, true),
            $items,
        );
    }

    public function getAdmin(int $id): UserNotificationResponse
    {
        return UserNotificationResponse::fromEntity($this->findEntity($id), true);
    }

    public function delete(int $id): void
    {
        $notification = $this->findEntity($id);
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }

    private function ownedNotification(User $user, int $id): UserNotification
    {
        $notification = $this->findEntity($id);
        if ($notification->getUser()?->getId() !== $user->getId()) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_NOTIFICATION);
        }

        return $notification;
    }

    private function findEntity(int $id): UserNotification
    {
        $notification = $this->userNotificationRepository->find($id);
        if (!$notification instanceof UserNotification) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_NOTIFICATION);
        }

        return $notification;
    }
}
