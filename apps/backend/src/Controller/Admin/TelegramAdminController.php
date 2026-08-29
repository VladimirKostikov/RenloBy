<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TelegramNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/telegram')]
#[IsGranted('ROLE_ADMIN')]
class TelegramAdminController extends AbstractController
{
    public function __construct(
        private readonly TelegramNotificationService $telegramNotificationService,
    ) {
    }

    #[Route('/status', name: 'admin_telegram_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json($this->telegramNotificationService->getStatus());
    }

    #[Route('/sync', name: 'admin_telegram_sync', methods: ['POST'])]
    public function sync(): JsonResponse
    {
        $result = $this->telegramNotificationService->syncPendingUpdates();

        return $this->json([
            ...$result,
            ...$this->telegramNotificationService->getStatus(),
        ]);
    }

    #[Route('/subscribers/{id}', name: 'admin_telegram_subscriber_update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateSubscriber(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        $active = is_array($data) && array_key_exists('isActive', $data)
            ? (bool) $data['isActive']
            : true;

        return $this->json($this->telegramNotificationService->setActive($id, $active));
    }

    #[Route('/subscribers/{id}', name: 'admin_telegram_subscriber_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteSubscriber(int $id): JsonResponse
    {
        $this->telegramNotificationService->deleteSubscriber($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
