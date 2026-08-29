<?php

declare(strict_types=1);

namespace App\Service;

final class ArticleImageCatalog
{
    private const IMAGES = [
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=1200&h=800&fit=crop&auto=format',
        'https://images.unsplash.com/photo-1560185007-c5ca9d2c014d?w=1200&h=800&fit=crop&auto=format',
    ];

    public function coverForIndex(int $index): string
    {
        return self::IMAGES[$index % count(self::IMAGES)];
    }

    /**
     * @return list<array{url: string, type: string}>
     */
    public function galleryForIndex(int $index, int $count = 2): array
    {
        $items = [];
        $total = count(self::IMAGES);
        for ($i = 0; $i < $count; ++$i) {
            $items[] = [
                'url' => self::IMAGES[($index + $i + 1) % $total],
                'type' => 'image',
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return self::IMAGES;
    }
}
