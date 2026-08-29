<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ListingRequest\CreateListingRequestRequest;
use App\Dto\ListingRequest\ListingRequestResponse;
use App\Dto\ListingRequest\UpdateListingRequestRequest;
use App\Entity\ListingRequest;
use App\Entity\User;
use App\Enum\ListingRequestStatus;
use App\Exception\ForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\ListingRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class ListingRequestService
{
    public function __construct(
        private readonly ListingRequestRepository $listingRequestRepository,
        private readonly ListingService $listingService,
        private readonly UserNotificationService $userNotificationService,
        private readonly ListingAnalyticsRecorder $listingAnalyticsRecorder,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<ListingRequestResponse>
     */
    public function list(): array
    {
        return array_map(
            static fn (ListingRequest $request) => ListingRequestResponse::fromEntity($request),
            $this->listingRequestRepository->findBy([], ['createdAt' => 'DESC'])
        );
    }

    /**
     * @return list<ListingRequestResponse>
     */
    public function listForSeller(User $user): array
    {
        return array_map(
            static fn (ListingRequest $request) => ListingRequestResponse::fromEntity($request),
            $this->listingRequestRepository->findByListingOwner($user),
        );
    }

    public function get(int $id): ListingRequestResponse
    {
        return ListingRequestResponse::fromEntity($this->findEntity($id));
    }

    public function createForListing(
        int $listingId,
        CreateListingRequestRequest $request,
        ?User $requester = null,
    ): ListingRequestResponse {
        $listing = $this->listingService->findEntity($listingId);

        $name = $request->name !== null ? trim($request->name) : null;
        if ($name === '') {
            $name = null;
        }

        $entity = (new ListingRequest())
            ->setListing($listing)
            ->setRequester($requester)
            ->setName($name)
            ->setPhone(trim($request->phone))
            ->setMessage(trim($request->message))
            ->setStatus(ListingRequestStatus::New)
            ->setIsTest($listing->isTest());

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->listingAnalyticsRecorder->recordMessage($listing);
        $this->userNotificationService->notifyListingContactRequestCreated($entity);
        $this->entityManager->flush();

        return ListingRequestResponse::fromEntity($entity);
    }

    public function update(int $id, UpdateListingRequestRequest $request): ListingRequestResponse
    {
        $entity = $this->findEntity($id);
        if ($request->status !== null) {
            $entity->setStatus($request->status);
        }
        $this->entityManager->flush();

        return ListingRequestResponse::fromEntity($entity);
    }

    public function delete(int $id): void
    {
        $entity = $this->findEntity($id);
        $entity->softDelete();
        $this->entityManager->flush();
    }

    public function deleteForSeller(User $user, int $id): void
    {
        $entity = $this->findEntity($id);
        if ($entity->getListing()?->getUser()?->getId() !== $user->getId()) {
            throw new ForbiddenException(ApiErrorCode::FORBIDDEN_LISTING_REQUEST);
        }

        $entity->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): ListingRequest
    {
        $entity = $this->listingRequestRepository->find($id);
        if (!$entity instanceof ListingRequest) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING_REQUEST);
        }

        return $entity;
    }
}
