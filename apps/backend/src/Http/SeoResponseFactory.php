<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\Response;

final class SeoResponseFactory
{
    private const DYNAMIC_HEADERS = [
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ];

    public function xml(string $content): Response
    {
        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/xml; charset=UTF-8',
                ...self::DYNAMIC_HEADERS,
            ],
        );
    }

    public function plainText(string $content): Response
    {
        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
                ...self::DYNAMIC_HEADERS,
            ],
        );
    }
}
