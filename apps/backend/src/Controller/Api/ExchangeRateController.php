<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/exchange-rates')]
class ExchangeRateController extends AbstractController
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {
    }

    #[Route('', name: 'api_exchange_rates_current', methods: ['GET'])]
    public function current(): JsonResponse
    {
        return $this->json($this->exchangeRateService->getRates());
    }
}
