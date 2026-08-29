<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Article;
use App\Entity\City;
use App\Entity\District;
use App\Entity\InfoPage;
use App\Entity\Listing;
use App\Repository\ArticleRepository;
use App\Repository\CityRepository;
use App\Repository\DistrictRepository;
use App\Repository\InfoPageRepository;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;

final class SitemapService
{
    public function __construct(
        private readonly string $siteUrl,
        private readonly ListingRepository $listingRepository,
        private readonly CityRepository $cityRepository,
        private readonly DistrictRepository $districtRepository,
        private readonly InfoPageRepository $infoPageRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function buildXml(): string
    {
        $this->entityManager->clear();

        $urls = array_merge(
            $this->staticUrls(),
            $this->infoPageUrls(),
            $this->articleUrls(),
            $this->locationUrls(),
            $this->listingUrls(),
        );

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    private function staticUrls(): array
    {
        return [
            $this->absolute('/'),
            $this->absolute('/rent'),
            $this->absolute('/sale'),
            $this->absolute('/search'),
            $this->absolute('/articles'),
        ];
    }

    private function infoPageUrls(): array
    {
        $pages = $this->infoPageRepository->createQueryBuilder('page')
            ->orderBy('page.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            fn (InfoPage $page) => $this->absolute('/info/' . $page->getSlug()),
            $pages,
        );
    }

    private function articleUrls(): array
    {
        return array_map(
            fn (Article $article) => $this->absolute('/articles/' . $article->getSlug()),
            $this->articleRepository->findPublishedOrdered(),
        );
    }

    private function locationUrls(): array
    {
        $urls = [];
        $regionSlugs = [];

        $cities = $this->cityRepository->createQueryBuilder('city')
            ->orderBy('city.name', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($cities as $city) {
            if (!$city instanceof City) {
                continue;
            }

            $regionSlug = $city->getRegionSlug();
            if ($regionSlug !== '') {
                $regionSlugs[$regionSlug] = true;
            }

            $urls[] = $this->absolute('/city/' . $city->getSlug());

            $districts = $this->districtRepository->createQueryBuilder('district')
                ->andWhere('district.city = :city')
                ->setParameter('city', $city)
                ->orderBy('district.name', 'ASC')
                ->getQuery()
                ->getResult();

            foreach ($districts as $district) {
                if (!$district instanceof District) {
                    continue;
                }

                $urls[] = $this->absolute('/city/' . $city->getSlug() . '/' . $district->getSlug());
            }
        }

        ksort($regionSlugs);
        foreach (array_keys($regionSlugs) as $regionSlug) {
            $urls[] = $this->absolute('/region/' . $regionSlug);
        }

        return $urls;
    }

    private function listingUrls(): array
    {
        $listings = $this->listingRepository->createQueryBuilder('listing')
            ->orderBy('listing.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            fn (Listing $listing) => $this->absolute('/listings/' . $listing->getId()),
            $listings,
        );
    }

    private function absolute(string $path): string
    {
        return rtrim($this->siteUrl, '/') . $path;
    }
}
