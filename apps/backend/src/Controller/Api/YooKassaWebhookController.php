<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\RequestMapper;
use App\Service\PaymentTransactionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/payments/yookassa')]
class YooKassaWebhookController extends AbstractController
{
    public function __construct(
        private readonly PaymentTransactionService $paymentTransactionService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('/webhook', name: 'api_yookassa_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $this->requestMapper->decodeJson($request);
        $updated = $this->paymentTransactionService->handleYooKassaWebhook($payload);

        if ($updated === null) {
            return $this->json(['ok' => true, 'handled' => false]);
        }

        return $this->json(['ok' => true, 'handled' => true, 'id' => $updated->id], Response::HTTP_OK);
    }
}
