<?php

declare(strict_types=1);

namespace App\Service;

final class ListingImageCatalog
{
    private const IMAGES = [
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1560185007-c5ca9d2c014d?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=640&h=480&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=640&h=480&fit=crop&auto=format',
    ];

    public function forIndex(int $index): array
    {
        $primary = self::IMAGES[$index % count(self::IMAGES)];
        $secondary = self::IMAGES[($index + 3) % count(self::IMAGES)];

        return [$primary, $secondary];
    }

    public function all(): array
    {
        return self::IMAGES;
    }
}
