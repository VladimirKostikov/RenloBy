<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\ListingRequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/listing-requests')]
#[IsGranted('ROLE_ADMIN')]
class ListingRequestAdminController extends AbstractController
{
    public function __construct(
        private readonly ListingRequestService $listingRequestService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_listing_requests_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->listingRequestService->list());
    }

    #[Route('/{id}', name: 'admin_listing_requests_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->listingRequestService->get($id));
    }

    #[Route('/{id}', name: 'admin_listing_requests_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->listingRequestService->update(
                $id,
                $this->requestMapper->mapUpdateListingRequest($this->requestMapper->decodeJson($request)),
            )
        );
    }

    #[Route('/{id}', name: 'admin_listing_requests_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->listingRequestService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
