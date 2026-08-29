<?php

declare(strict_types=1);

namespace App\Service;

final class ListingAddressNormalizer
{
    public function streetFromAddress(string $address): string
    {
        $trimmed = trim($address);
        if ($trimmed === '') {
            return '';
        }

        $parts = preg_split('/\s*,\s*/u', $trimmed, 2);
        if (!is_array($parts) || $parts === []) {
            return $trimmed;
        }

        return trim((string) $parts[0]);
    }
}
