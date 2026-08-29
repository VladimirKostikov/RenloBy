<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\ListingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/listings')]
#[IsGranted('ROLE_ADMIN')]
class ListingAdminController extends AbstractController
{
    public function __construct(
        private readonly ListingService $listingService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_listings_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        return $this->json($this->listingService->searchAdmin($this->requestMapper->mapListingSearch($request)));
    }

    #[Route('/{id}', name: 'admin_listings_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->listingService->getAdmin($id));
    }

    #[Route('', name: 'admin_listings_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $listing = $this->listingService->create(
            $this->requestMapper->mapCreateListing($this->requestMapper->decodeJson($request))
        );

        return $this->json($listing, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_listings_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->listingService->update($id, $this->requestMapper->mapUpdateListing($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'admin_listings_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->listingService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
