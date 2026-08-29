<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ListingReport\CreateListingReportRequest;
use App\Dto\ListingReport\ListingReportResponse;
use App\Dto\ListingReport\UpdateListingReportRequest;
use App\Entity\ListingReport;
use App\Entity\User;
use App\Enum\ListingReportStatus;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\ListingReportRepository;
use Doctrine\ORM\EntityManagerInterface;

class ListingReportService
{
    public function __construct(
        private readonly ListingReportRepository $listingReportRepository,
        private readonly ListingService $listingService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<ListingReportResponse>
     */
    public function list(): array
    {
        return array_map(
            static fn (ListingReport $report) => ListingReportResponse::fromEntity($report),
            $this->listingReportRepository->findBy([], ['createdAt' => 'DESC'])
        );
    }

    /**
     * @return list<ListingReportResponse>
     */
    public function listForSeller(User $user): array
    {
        return array_map(
            static fn (ListingReport $report) => ListingReportResponse::fromEntity($report),
            $this->listingReportRepository->findByListingOwner($user),
        );
    }

    public function get(int $id): ListingReportResponse
    {
        return ListingReportResponse::fromEntity($this->findEntity($id));
    }

    public function createForListing(int $listingId, CreateListingReportRequest $request): ListingReportResponse
    {
        $listing = $this->listingService->findEntity($listingId);

        $report = (new ListingReport())
            ->setListing($listing)
            ->setReason($request->reason)
            ->setComment(trim($request->comment))
            ->setStatus(ListingReportStatus::New)
            ->setIsTest($listing->isTest());

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        return ListingReportResponse::fromEntity($report);
    }

    public function update(int $id, UpdateListingReportRequest $request): ListingReportResponse
    {
        $report = $this->findEntity($id);
        if ($request->status !== null) {
            $report->setStatus($request->status);
        }
        $this->entityManager->flush();

        return ListingReportResponse::fromEntity($report);
    }

    public function delete(int $id): void
    {
        $report = $this->findEntity($id);
        $report->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): ListingReport
    {
        $report = $this->listingReportRepository->find($id);
        if (!$report instanceof ListingReport) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING_REPORT);
        }

        return $report;
    }
}
