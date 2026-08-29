<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\AccountService;
use App\Service\ListingAnalyticsService;
use App\Service\ListingReportService;
use App\Service\ListingRequestService;
use App\Service\ListingService;
use App\Service\MediaUploadService;
use App\Service\SellerAnalyticsService;
use App\Service\SellerListingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/me')]
#[IsGranted('ROLE_USER')]
class MeController extends AbstractController
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly ListingService $listingService,
        private readonly SellerListingService $sellerListingService,
        private readonly SellerAnalyticsService $sellerAnalyticsService,
        private readonly ListingAnalyticsService $listingAnalyticsService,
        private readonly ListingReportService $listingReportService,
        private readonly ListingRequestService $listingRequestService,
        private readonly MediaUploadService $mediaUploadService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/summary', name: 'api_me_summary', methods: ['GET'])]
    public function summary(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->accountService->getSummary($user));
    }

    #[Route('/analytics', name: 'api_me_analytics', methods: ['GET'])]
    public function analytics(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->sellerAnalyticsService->getAnalytics($user));
    }

    #[Route('/analytics/listings', name: 'api_me_analytics_listings', methods: ['GET'])]
    public function analyticsListings(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) > 120) {
            $q = mb_substr($q, 0, 120);
        }

        return $this->json($this->listingAnalyticsService->listOptions($user, $page, $limit, $q));
    }

    #[Route('/analytics/listings/{id}', name: 'api_me_analytics_listing', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function analyticsListing(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $range = (string) $request->query->get('range', 'week');
        if (!in_array($range, ['day', 'week', 'month'], true)) {
            $range = 'week';
        }

        return $this->json($this->listingAnalyticsService->getDetail($user, $id, $range));
    }

    #[Route('/listings', name: 'api_me_listings', methods: ['GET'])]
    public function listings(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $search = $this->requestMapper->mapListingSearch($request);

        return $this->json($this->listingService->searchForUser($user, $search));
    }

    #[Route('/listings', name: 'api_me_listings_create', methods: ['POST'])]
    public function createListing(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $dto = $this->requestMapper->mapCreateSellerListing($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json(
            $this->sellerListingService->create($user, $dto),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/listings/{id}', name: 'api_me_listings_update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateListing(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $dto = $this->requestMapper->mapUpdateSellerListing($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json($this->sellerListingService->update($user, $id, $dto));
    }

    #[Route('/listings/{id}/publish', name: 'api_me_listings_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publishListing(int $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->sellerListingService->publish($user, $id));
    }

    #[Route('/listings/{id}/archive', name: 'api_me_listings_archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archiveListing(int $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->sellerListingService->archive($user, $id));
    }

    #[Route('/listings/{id}', name: 'api_me_listings_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteListing(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->sellerListingService->deleteRemovable($user, $id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/listing-reports', name: 'api_me_listing_reports', methods: ['GET'])]
    public function listingReports(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->listingReportService->listForSeller($user));
    }

    #[Route('/listing-requests', name: 'api_me_listing_requests', methods: ['GET'])]
    public function listingRequests(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->listingRequestService->listForSeller($user));
    }

    #[Route('/listing-requests/{id}', name: 'api_me_listing_requests_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteListingRequest(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->listingRequestService->deleteForSeller($user, $id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/media/upload', name: 'api_me_media_upload', methods: ['POST'])]
    public function uploadMedia(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw new ValidationException(['file' => 'validation.failed']);
        }

        return $this->json(
            $this->mediaUploadService->uploadListing($file, $user),
            Response::HTTP_CREATED,
        );
    }

    private function assertValid(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return;
        }

        $fields = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $property = (string) $violation->getPropertyPath();
            if ($property !== '' && !isset($fields[$property])) {
                $fields[$property] = (string) $violation->getMessage();
            }
        }

        throw new ValidationException($fields);
    }
}
