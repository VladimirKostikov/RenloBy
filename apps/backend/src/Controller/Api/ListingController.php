<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\RequestMapper;
use App\Service\ListingAddressSuggestService;
use App\Service\ListingAnalyticsRecorder;
use App\Service\ListingMarketService;
use App\Service\ListingService;
use App\Repository\ListingRepository;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/listings')]
class ListingController extends AbstractController
{
    public function __construct(
        private readonly ListingService $listingService,
        private readonly ListingAddressSuggestService $addressSuggestService,
        private readonly ListingRepository $listingRepository,
        private readonly ListingAnalyticsRecorder $listingAnalyticsRecorder,
        private readonly ListingMarketService $listingMarketService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'api_listings_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $result = $this->listingService->search($this->requestMapper->mapListingSearch($request));

        return $this->json($result);
    }

    #[Route('/address-suggest', name: 'api_listings_address_suggest', methods: ['GET'])]
    public function addressSuggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $limit = max(1, min(20, (int) $request->query->get('limit', 10)));

        return $this->json($this->addressSuggestService->suggest($query, $limit));
    }

    #[Route('/{id}/events/contact', name: 'api_listings_event_contact', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function recordContact(int $id, Request $request): JsonResponse
    {
        $listing = $this->listingRepository->find($id);
        if ($listing === null) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING);
        }

        $payload = [];
        try {
            $payload = $this->requestMapper->decodeJson($request);
        } catch (\Throwable) {
            $payload = [];
        }
        $type = (string) ($payload['type'] ?? 'contact');
        if ($type === 'message') {
            $this->listingAnalyticsRecorder->recordMessage($listing);
        } else {
            $this->listingAnalyticsRecorder->recordContactOpen($listing);
        }

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/market', name: 'api_listings_market', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function market(int $id): JsonResponse
    {
        return $this->json($this->listingMarketService->snapshotForId($id));
    }

    #[Route('/{id}', name: 'api_listings_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->listingService->get($id));
    }

    #[Route('', name: 'api_listings_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $listing = $this->listingService->create(
            $this->requestMapper->mapCreateListing($this->requestMapper->decodeJson($request))
        );

        return $this->json($listing, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_listings_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->listingService->update($id, $this->requestMapper->mapUpdateListing($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'api_listings_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->listingService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
