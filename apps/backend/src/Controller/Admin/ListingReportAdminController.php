<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\ListingReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/listing-reports')]
#[IsGranted('ROLE_ADMIN')]
class ListingReportAdminController extends AbstractController
{
    public function __construct(
        private readonly ListingReportService $listingReportService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_listing_reports_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->listingReportService->list());
    }

    #[Route('/{id}', name: 'admin_listing_reports_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->listingReportService->get($id));
    }

    #[Route('/{id}', name: 'admin_listing_reports_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->listingReportService->update(
                $id,
                $this->requestMapper->mapUpdateListingReport($this->requestMapper->decodeJson($request)),
            )
        );
    }

    #[Route('/{id}', name: 'admin_listing_reports_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->listingReportService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
