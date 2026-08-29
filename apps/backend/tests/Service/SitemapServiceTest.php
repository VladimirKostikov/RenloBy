<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Article;
use App\Entity\City;
use App\Entity\InfoPage;
use App\Entity\Listing;
use App\Repository\ArticleRepository;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\InfoPageRepository;
use App\Repository\ListingRepository;
use App\Service\SitemapService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class SitemapServiceTest extends TestCase
{
    public function testBuildXmlContainsStaticAndEntityUrls(): void
    {
        $city = $this->createMock(City::class);
        $city->method('getSlug')->willReturn('minsk');
        $city->method('getRegionSlug')->willReturn('minsk-city');

        $infoPage = $this->createMock(InfoPage::class);
        $infoPage->method('getSlug')->willReturn('deal-safety');

        $article = $this->createMock(Article::class);
        $article->method('getSlug')->willReturn('kak-vybrat-kvartiru-v-minske');

        $listing = $this->createMock(Listing::class);
        $listing->method('getId')->willReturn(15);

        $cityRepository = $this->createCityRepository([$city]);
        $districtRepository = $this->createDistrictRepository([]);
        $infoPageRepository = $this->createInfoPageRepository([$infoPage]);
        $articleRepository = $this->createArticleRepository([$article]);
        $listingRepository = $this->createListingRepository([$listing]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('clear');

        $service = new SitemapService(
            'https://renlo.by',
            $listingRepository,
            $cityRepository,
            $districtRepository,
            $infoPageRepository,
            $articleRepository,
            $entityManager,
        );

        $xml = $service->buildXml();

        self::assertStringContainsString('<urlset', $xml);
        self::assertStringContainsString('https://renlo.by/rent', $xml);
        self::assertStringContainsString('https://renlo.by/articles', $xml);
        self::assertStringContainsString('https://renlo.by/city/minsk', $xml);
        self::assertStringContainsString('https://renlo.by/region/minsk-city', $xml);
        self::assertStringContainsString('https://renlo.by/info/deal-safety', $xml);
        self::assertStringContainsString('https://renlo.by/articles/kak-vybrat-kvartiru-v-minske', $xml);
        self::assertStringContainsString('https://renlo.by/listings/15', $xml);
    }

    public function testBuildXmlReflectsFreshListingQueryOnEachCall(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('clear');

        $cityRepository = $this->createCityRepository([]);
        $districtRepository = $this->createDistrictRepository([]);
        $infoPageRepository = $this->createInfoPageRepository([]);
        $articleRepository = $this->createArticleRepository([]);
        $listingRepository = $this->createMock(ListingRepository::class);

        $firstListing = $this->createMock(Listing::class);
        $firstListing->method('getId')->willReturn(1);
        $secondListing = $this->createMock(Listing::class);
        $secondListing->method('getId')->willReturn(2);

        $listingRepository->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $this->createListingQueryBuilder([$firstListing]),
                $this->createListingQueryBuilder([$firstListing, $secondListing]),
            );

        $service = new SitemapService(
            'https://renlo.by',
            $listingRepository,
            $cityRepository,
            $districtRepository,
            $infoPageRepository,
            $articleRepository,
            $entityManager,
        );

        $firstXml = $service->buildXml();
        $secondXml = $service->buildXml();

        self::assertStringNotContainsString('https://renlo.by/listings/2', $firstXml);
        self::assertStringContainsString('https://renlo.by/listings/2', $secondXml);
    }

    private function createCityRepository(array $cities): CityRepository
    {
        $repository = $this->createMock(CityRepository::class);
        $repository->method('createQueryBuilder')->willReturn(
            $this->createEntityQueryBuilder($cities),
        );

        return $repository;
    }

    private function createDistrictRepository(array $districts): DistrictRepository
    {
        $repository = $this->createMock(DistrictRepository::class);
        $repository->method('createQueryBuilder')->willReturn(
            $this->createEntityQueryBuilder($districts),
        );

        return $repository;
    }

    private function createInfoPageRepository(array $pages): InfoPageRepository
    {
        $repository = $this->createMock(InfoPageRepository::class);
        $repository->method('createQueryBuilder')->willReturn(
            $this->createEntityQueryBuilder($pages),
        );

        return $repository;
    }

    private function createArticleRepository(array $articles): ArticleRepository
    {
        $repository = $this->createMock(ArticleRepository::class);
        $repository->method('findPublishedOrdered')->willReturn($articles);

        return $repository;
    }

    private function createListingRepository(array $listings): ListingRepository
    {
        $repository = $this->createMock(ListingRepository::class);
        $repository->method('createQueryBuilder')->willReturn(
            $this->createListingQueryBuilder($listings),
        );

        return $repository;
    }

    private function createListingQueryBuilder(array $listings): QueryBuilder
    {
        return $this->createEntityQueryBuilder($listings);
    }

    private function createEntityQueryBuilder(array $result): QueryBuilder
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($result);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }
}
