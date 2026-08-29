<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SeoMeta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeoMeta>
 */
class SeoMetaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeoMeta::class);
    }

    public function findOneByPageKeyAndLocale(string $pageKey, string $locale): ?SeoMeta
    {
        return $this->findOneBy(['pageKey' => $pageKey, 'locale' => $locale]);
    }

    /**
     * @return list<SeoMeta>
     */
    public function findByLocale(string $locale): array
    {
        return $this->findBy(['locale' => $locale], ['pageKey' => 'ASC']);
    }
}
