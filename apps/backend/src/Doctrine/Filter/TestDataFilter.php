<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use App\Entity\City;
use App\Entity\District;
use App\Entity\HeadSnippet;
use App\Entity\MetroStation;
use App\Entity\User;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class TestDataFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (self::shouldSkipEntity($targetEntity->getName())) {
            return '';
        }

        if (!$targetEntity->hasField('isTest')) {
            return '';
        }

        $isTest = self::parseIsTestParameter($this->getParameter('is_test'));

        return sprintf('%s.is_test = %s', $targetTableAlias, $isTest ? 'TRUE' : 'FALSE');
    }

    public static function shouldSkipEntity(string $entityClass): bool
    {
        return in_array($entityClass, [
            User::class,
            City::class,
            District::class,
            MetroStation::class,
            HeadSnippet::class,
        ], true);
    }

    public static function parseIsTestParameter(string $quotedOrRaw): bool
    {
        $raw = trim($quotedOrRaw, "'\"");

        if ($raw === '') {
            return false;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
}
