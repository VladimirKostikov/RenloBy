<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\SiteSettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/site-settings')]
class SiteSettingsController extends AbstractController
{
    public function __construct(
        private readonly SiteSettingsService $siteSettingsService,
    ) {
    }

    #[Route('', name: 'api_site_settings_current', methods: ['GET'])]
    public function current(): JsonResponse
    {
        return $this->json($this->siteSettingsService->getCurrent());
    }
}
