<?php

declare(strict_types=1);

namespace App\Util;

final class UserFullName
{
    public static function compose(?string $lastName, ?string $firstName, ?string $patronymic): string
    {
        $parts = [];
        foreach ([$lastName, $firstName, $patronymic] as $part) {
            if ($part === null) {
                continue;
            }
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $parts[] = $trimmed;
            }
        }

        return implode(' ', $parts);
    }

    public static function normalizePart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
