<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\TariffService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tariffs')]
class TariffController extends AbstractController
{
    public function __construct(
        private readonly TariffService $tariffService,
    ) {
    }

    #[Route('', name: 'api_tariffs_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->tariffService->list());
    }
}
