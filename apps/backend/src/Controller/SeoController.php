<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\SeoResponseFactory;
use App\Service\RobotsTxtService;
use App\Service\SitemapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeoController extends AbstractController
{
    public function __construct(
        private readonly RobotsTxtService $robotsTxtService,
        private readonly SitemapService $sitemapService,
        private readonly SeoResponseFactory $seoResponseFactory,
    ) {
    }

    #[Route('/robots.txt', name: 'seo_robots', methods: ['GET'])]
    public function robots(): Response
    {
        return $this->seoResponseFactory->plainText($this->robotsTxtService->build());
    }

    #[Route('/sitemap.xml', name: 'seo_sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        return $this->seoResponseFactory->xml($this->sitemapService->buildXml());
    }
}
