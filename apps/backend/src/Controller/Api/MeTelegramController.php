<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\SellerTelegramService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/me/telegram')]
#[IsGranted('ROLE_USER')]
class MeTelegramController extends AbstractController
{
    public function __construct(
        private readonly SellerTelegramService $sellerTelegramService,
    ) {
    }

    #[Route('', name: 'api_me_telegram_status', methods: ['GET'])]
    public function status(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->sellerTelegramService->getStatus($user));
    }

    #[Route('/disconnect', name: 'api_me_telegram_disconnect', methods: ['POST'])]
    public function disconnect(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->sellerTelegramService->disconnect($user));
    }
}
