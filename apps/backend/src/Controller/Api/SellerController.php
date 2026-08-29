<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\SellerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sellers')]
class SellerController extends AbstractController
{
    public function __construct(
        private readonly SellerService $sellerService,
    ) {
    }

    #[Route('/{id}', name: 'api_sellers_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->sellerService->getProfile($id));
    }

    #[Route('/{id}/listings', name: 'api_sellers_listings', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listings(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 12)));

        return $this->json($this->sellerService->getListings($id, $page, $limit));
    }
}
