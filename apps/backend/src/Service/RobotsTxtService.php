<?php

declare(strict_types=1);

namespace App\Service;

final class RobotsTxtService
{
    public function __construct(
        private readonly string $siteUrl,
    ) {
    }

    public function build(): string
    {
        $sitemap = rtrim($this->siteUrl, '/') . '/sitemap.xml';

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /promotion/payment',
            'Sitemap: ' . $sitemap,
            '',
        ]);
    }
}
