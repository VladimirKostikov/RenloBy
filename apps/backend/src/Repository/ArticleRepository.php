<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findBySlug(string $slug): ?Article
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findPublishedBySlug(string $slug): ?Article
    {
        return $this->findOneBy(['slug' => $slug, 'isPublished' => true]);
    }

    /**
     * @return list<Article>
     */
    public function findPublishedOrdered(): array
    {
        return $this->createQueryBuilder('article')
            ->andWhere('article.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('article.publishedAt', 'DESC')
            ->addOrderBy('article.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Article>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('article')
            ->orderBy('article.publishedAt', 'DESC')
            ->addOrderBy('article.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
