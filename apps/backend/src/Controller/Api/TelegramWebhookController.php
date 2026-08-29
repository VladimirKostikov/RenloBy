<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\ApiErrorCode;
use App\Service\TelegramNotificationService;
use App\Service\TelegramWebhookGuard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/telegram')]
class TelegramWebhookController extends AbstractController
{
    public function __construct(
        private readonly TelegramNotificationService $telegramNotificationService,
        private readonly TelegramWebhookGuard $telegramWebhookGuard,
    ) {
    }

    #[Route('/webhook', name: 'api_telegram_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        if (!$this->telegramWebhookGuard->isAuthorized($request)) {
            return $this->json(['error' => ApiErrorCode::WEBHOOK_FORBIDDEN], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($payload)) {
            return $this->json(['ok' => true], Response::HTTP_OK);
        }

        $this->telegramNotificationService->handleWebhookUpdate($payload);

        return $this->json(['ok' => true]);
    }
}
