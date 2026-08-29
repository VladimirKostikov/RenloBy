<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\MetroStation;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingReportReason;
use App\Enum\ListingReportStatus;
use App\Enum\ListingRequestStatus;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;
use App\Factory\AiPreferenceFactory;
use App\Factory\CityFactory;
use App\Factory\DistrictFactory;
use App\Factory\ArticleFactory;
use App\Factory\InfoPageFactory;
use App\Factory\ListingFactory;
use App\Factory\ListingReportFactory;
use App\Factory\ListingRequestFactory;
use App\Factory\MediaFileFactory;
use App\Factory\MetroStationFactory;
use App\Factory\SeoMetaFactory;
use App\Factory\SiteSettingsFactory;
use App\Factory\TariffFactory;
use App\Factory\UserFactory;
use App\Service\AdminNationwideListingSeeder;
use App\Service\ArticleImageCatalog;
use App\Service\ListingImageCatalog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /** @var array<string, array{0: float, 1: float}> */
    private const CITY_COORDS = [
        'minsk' => [53.9045, 27.5615],
        'borisov' => [54.2279, 28.5050],
        'soligorsk' => [52.7928, 27.5414],
        'molodechno' => [54.3107, 26.8512],
        'brest-city' => [52.0976, 23.7341],
        'vitebsk-city' => [55.1904, 30.2049],
        'gomel-city' => [52.4345, 30.9754],
        'grodno-city' => [53.6693, 23.8131],
        'mogilev-city' => [53.8945, 30.3307],
        'zhodino' => [53.3447, 28.3236],
        'berezino' => [53.8378, 27.6906],
        'mir' => [53.4514, 26.4729],
        'motol' => [52.3147, 25.5739],
        'chechersk' => [52.9164, 30.9179],
    ];

    /** @var array<string, array<string, string>> */
    private const CITY_DISTRICTS = [
        'minsk' => [
            'Центральный' => 'centralny',
            'Советский' => 'sovetsky',
            'Первомайский' => 'pervomaysky',
            'Партизанский' => 'partizansky',
            'Заводской' => 'zavodskoy',
            'Ленинский' => 'leninsky',
            'Октябрьский' => 'oktyabrsky',
            'Московский' => 'moskovsky',
            'Фрунзенский' => 'frunzensky',
        ],
        'brest-city' => [
            'Центральный' => 'centralny',
            'Ленинский' => 'leninsky',
            'Московский' => 'moskovsky',
        ],
        'vitebsk-city' => [
            'Октябрьский' => 'oktyabrsky',
            'Первомайский' => 'pervomaysky',
            'Железнодорожный' => 'zheleznodorozhny',
        ],
        'gomel-city' => [
            'Центральный' => 'centralny',
            'Советский' => 'sovetsky',
            'Новобелицкий' => 'novobelitsky',
        ],
        'grodno-city' => [
            'Ленинский' => 'leninsky',
            'Октябрьский' => 'oktyabrsky',
        ],
        'mogilev-city' => [
            'Ленинский' => 'leninsky',
            'Октябрьский' => 'oktyabrsky',
        ],
        'borisov' => [
            'Центральный' => 'centralny',
        ],
        'soligorsk' => [
            'Центральный' => 'centralny',
        ],
        'molodechno' => [
            'Центральный' => 'centralny',
        ],
        'zhodino' => [
            'Центральный' => 'centralny',
        ],
        'berezino' => [
            'Центральный' => 'centralny',
        ],
        'mir' => [
            'Центральный' => 'centralny',
        ],
        'motol' => [
            'Центральный' => 'centralny',
        ],
        'chechersk' => [
            'Центральный' => 'centralny',
        ],
    ];

    /** @var array<string, int> */
    private const LISTINGS_PER_CITY = [
        'minsk' => 50,
        'brest-city' => 12,
        'vitebsk-city' => 12,
        'gomel-city' => 12,
        'grodno-city' => 12,
        'mogilev-city' => 12,
        'borisov' => 6,
        'soligorsk' => 6,
        'molodechno' => 6,
        'zhodino' => 4,
        'berezino' => 4,
        'mir' => 4,
        'motol' => 4,
        'chechersk' => 4,
    ];

    /** @var list<array{0: string, 1: string, 2: int, 3: int, 4: float, 5: int, 6: bool, 7: bool, 8: bool, 9: bool, 10: string}> */
    private const RENT_CATALOG_SEED = [
        ['apartment', 'daily', 450, 2, 58.0, 7, true, true, false, true, 'ул. Петра Мстиславца, 18'],
        ['apartment', 'daily', 380, 1, 42.0, 3, false, true, true, true, 'пр. Независимости, 25'],
        ['apartment', 'long', 650, 3, 74.0, 9, true, false, false, true, 'ул. Ленина, 10'],
        ['apartment', 'long', 520, 2, 61.0, 5, true, true, true, true, 'ул. Кальварийская, 17'],
        ['apartment', 'daily', 320, 1, 36.0, 2, false, false, true, true, 'пр. Победителей, 84'],
        ['apartment', 'long', 780, 3, 82.0, 11, true, true, false, true, 'ул. Сурганова, 50'],
        ['house', 'long', 1200, 4, 120.0, 2, true, false, false, true, 'ул. Якуба Коласа, 37'],
        ['house', 'daily', 890, 3, 95.0, 1, true, true, true, true, 'ул. Киселёва, 21'],
        ['house', 'long', 980, 4, 110.0, 2, false, true, false, true, 'пр. Дзержинского, 119'],
        ['room', 'daily', 220, 1, 18.0, 4, false, true, true, true, 'ул. Немига, 5'],
        ['room', 'long', 280, 1, 20.0, 6, true, false, true, true, 'ул. Купаловская, 12'],
        ['room', 'daily', 190, 1, 16.0, 8, false, false, true, false, 'ул. Октябрьская, 33'],
        ['commercial', 'long', 1500, 1, 85.0, 3, true, false, false, true, 'ул. Немига, 12'],
        ['commercial', 'long', 2200, 2, 120.0, 1, true, true, true, true, 'пр. Победителей, 9'],
        ['apartment', 'daily', 410, 2, 55.0, 12, true, true, true, true, 'ул. Михалово, 7'],
        ['apartment', 'long', 590, 2, 63.0, 4, false, true, false, true, 'ул. Малиновка, 14'],
        ['apartment', 'daily', 350, 1, 39.0, 10, true, false, true, true, 'ул. Московская, 22'],
        ['apartment', 'long', 720, 3, 78.0, 8, true, true, false, true, 'ул. Восток, 9'],
        ['house', 'long', 1100, 5, 135.0, 1, true, true, false, true, 'ул. Аэродромная, 44'],
        ['room', 'long', 260, 1, 19.0, 3, false, true, true, true, 'ул. Парк Челюскинцев, 2'],
        ['apartment', 'daily', 480, 2, 57.0, 6, false, false, false, true, 'ул. Площадь Ленина, 1'],
        ['apartment', 'long', 840, 3, 88.0, 14, true, true, true, true, 'ул. Фрунзенская, 28'],
    ];

    /** @var list<array{0: string, 1: int, 2: int, 3: float, 4: int, 5: bool, 6: bool, 7: bool, 8: bool, 9: string}> */
    private const SALE_CATALOG_SEED = [
        ['apartment', 145000, 2, 58.0, 7, true, true, true, true, 'ул. Петра Мстиславца, 18'],
        ['apartment', 98000, 1, 42.0, 3, false, true, false, true, 'пр. Независимости, 25'],
        ['apartment', 175000, 3, 74.0, 9, true, false, true, true, 'ул. Ленина, 10'],
        ['apartment', 132000, 2, 61.0, 5, true, true, true, false, 'ул. Кальварийская, 17'],
        ['apartment', 89000, 1, 36.0, 2, false, false, true, true, 'пр. Победителей, 84'],
        ['apartment', 198000, 3, 82.0, 11, true, true, false, true, 'ул. Сурганова, 50'],
        ['house', 320000, 4, 120.0, 2, true, true, true, true, 'ул. Якуба Коласа, 37'],
        ['house', 245000, 3, 95.0, 1, true, false, true, true, 'ул. Киселёва, 21'],
        ['house', 278000, 4, 110.0, 2, false, true, false, true, 'пр. Дзержинского, 119'],
        ['room', 42000, 1, 18.0, 4, false, true, true, false, 'ул. Немига, 5'],
        ['room', 55000, 1, 20.0, 6, true, true, false, true, 'ул. Купаловская, 12'],
        ['room', 38000, 1, 16.0, 8, false, false, false, false, 'ул. Октябрьская, 33'],
        ['commercial', 185000, 1, 90.0, 2, true, true, false, true, 'ул. Немига, 12'],
        ['commercial', 310000, 2, 140.0, 1, false, true, true, true, 'пр. Победителей, 9'],
        ['apartment', 125000, 2, 55.0, 12, true, true, true, true, 'ул. Михалово, 7'],
        ['apartment', 158000, 2, 63.0, 4, false, true, true, true, 'ул. Малиновка, 14'],
        ['apartment', 94000, 1, 39.0, 10, true, false, true, true, 'ул. Московская, 22'],
        ['apartment', 185000, 3, 78.0, 8, true, true, false, true, 'ул. Восток, 9'],
        ['house', 295000, 5, 135.0, 1, true, true, true, true, 'ул. Аэродромная, 44'],
        ['room', 48000, 1, 19.0, 3, false, true, true, true, 'ул. Парк Челюскинцев, 2'],
        ['apartment', 115000, 2, 57.0, 6, false, false, false, true, 'ул. Площадь Ленина, 1'],
        ['apartment', 210000, 3, 88.0, 14, true, true, true, true, 'ул. Фрунзенская, 28'],
    ];

    /** @var array<string, list<string>> */
    private const CITY_ADDRESSES = [
        'minsk' => [
            'пр. Независимости, 25',
            'ул. Ленина, 10',
            'ул. Кальварийская, 17',
            'пр. Победителей, 84',
            'ул. Сурганова, 50',
            'ул. Якуба Коласа, 37',
            'ул. Киселёва, 21',
            'пр. Дзержинского, 119',
        ],
        'brest-city' => [
            'ул. Советская, 12',
            'ул. Московская, 45',
            'ул. Гоголя, 8',
            'пр. Машерова, 17',
        ],
        'vitebsk-city' => [
            'ул. Ленина, 5',
            'пр. Фрунзе, 33',
            'ул. Замковая, 2',
            'ул. Гагарина, 14',
        ],
        'gomel-city' => [
            'пр. Ленина, 10',
            'ул. Советская, 28',
            'ул. Речицкий проспект, 5',
            'ул. Ирининская, 19',
        ],
        'grodno-city' => [
            'ул. Советская, 18',
            'ул. Ожешко, 7',
            'ул. Пушкина, 29',
            'пр. Янки Купалы, 44',
        ],
        'mogilev-city' => [
            'пр. Победителей, 21',
            'ул. Первомайская, 36',
            'ул. Ленинская, 4',
            'ул. Мира, 11',
        ],
        'borisov' => [
            'ул. Чапаева, 9',
            'ул. Гагарина, 22',
            'ул. Красноармейская, 3',
        ],
        'soligorsk' => [
            'ул. Заслонова, 15',
            'ул. Железнодорожная, 6',
            'пр. Мира, 4',
        ],
        'molodechno' => [
            'ул. Виленская, 13',
            'ул. Мира, 8',
            'ул. Молодёжная, 2',
        ],
        'zhodino' => [
            'ул. 40 лет Октября, 7',
            'ул. Советская, 14',
            'ул. Мира, 3',
        ],
        'berezino' => [
            'ул. Советская, 11',
            'ул. Комсомольская, 5',
            'ул. Ленина, 19',
        ],
        'mir' => [
            'ул. Кирова, 4',
            'ул. Слободская, 9',
            'ул. Комсомольская, 2',
        ],
        'motol' => [
            'ул. Центральная, 6',
            'ул. Советская, 18',
            'ул. Школьная, 3',
        ],
        'chechersk' => [
            'ул. Советская, 21',
            'ул. Ленина, 8',
            'ул. Комсомольская, 12',
        ],
    ];

    public function __construct(
        private readonly UserFactory $userFactory,
        private readonly CityFactory $cityFactory,
        private readonly DistrictFactory $districtFactory,
        private readonly MetroStationFactory $metroStationFactory,
        private readonly ListingFactory $listingFactory,
        private readonly ListingRequestFactory $listingRequestFactory,
        private readonly ListingReportFactory $listingReportFactory,
        private readonly InfoPageFactory $infoPageFactory,
        private readonly ArticleFactory $articleFactory,
        private readonly SeoMetaFactory $seoMetaFactory,
        private readonly TariffFactory $tariffFactory,
        private readonly SiteSettingsFactory $siteSettingsFactory,
        private readonly MediaFileFactory $mediaFileFactory,
        private readonly AiPreferenceFactory $aiPreferenceFactory,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ListingImageCatalog $imageCatalog,
        private readonly ArticleImageCatalog $articleImageCatalog,
        private readonly AdminNationwideListingSeeder $adminNationwideListingSeeder,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->seedUsers($manager);
        $user = $users['user@renlo.local'];
        $admin = $users['admin@renlo.local'];
        $listingOwners = [
            $users['seller@renlo.local'],
            $users['agent@renlo.local'],
            $users['user@renlo.local'],
            $admin,
        ];

        $citiesBySlug = $this->createCities($manager);
        $districtsByCity = $this->createDistricts($manager, $citiesBySlug);
        $metroStations = $this->createMetroStations($manager, $citiesBySlug['minsk']);
        $listings = [
            ...$this->createListings($manager, $listingOwners, $citiesBySlug, $districtsByCity, $metroStations),
            ...$this->createRentCatalogListings($manager, $listingOwners, $citiesBySlug['minsk'], $districtsByCity['minsk'], $metroStations),
            ...$this->createSaleCatalogListings($manager, $listingOwners, $citiesBySlug['minsk'], $districtsByCity['minsk'], $metroStations),
            ...$this->adminNationwideListingSeeder->seedIntoManager(
                $manager,
                $admin,
                $citiesBySlug,
                $districtsByCity,
                $metroStations,
            ),
        ];
        $adminListings = array_values(array_filter(
            $listings,
            static fn (Listing $listing): bool => $listing->getUser() === $admin,
        ));
        $this->createListingRequests($manager, $adminListings, $users);
        $this->createListingReports($manager, $adminListings);
        $this->createInfoPages($manager);
        $this->createArticles($manager);
        $this->createSeoMeta($manager);
        $this->createTariffs($manager);
        $this->createSiteSettings($manager);
        $this->createMediaFiles($manager, $user);
        $this->createAiPreferences($manager, $user);

        $manager->flush();
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(ObjectManager $manager): array
    {
        $users = [];

        foreach (UserSeedDefinitions::ENTRIES as $definition) {
            $user = $this->userFactory->create(
                $definition['email'],
                '',
                $definition['roles'],
            );
            $user
                ->setPassword($this->passwordHasher->hashPassword($user, $definition['password']))
                ->setNameParts(
                    $definition['lastName'],
                    $definition['firstName'],
                    $definition['patronymic'],
                )
                ->setPhone($definition['phone'])
                ->setTelegram($definition['telegram'])
                ->setWhatsapp($definition['whatsapp'])
                ->setViber($definition['viber'])
                ->setInstagram($definition['instagram'])
                ->setPhoto($definition['photo']);

            $manager->persist($user);
            $users[$definition['email']] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, City>
     */
    private function createCities(ObjectManager $manager): array
    {
        $cities = [
            ['Минск', 'minsk', 'minsk-city'],
            ['Борисов', 'borisov', 'minsk-region'],
            ['Солигорск', 'soligorsk', 'minsk-region'],
            ['Молодечно', 'molodechno', 'minsk-region'],
            ['Брест', 'brest-city', 'brest'],
            ['Витебск', 'vitebsk-city', 'vitebsk'],
            ['Гомель', 'gomel-city', 'gomel'],
            ['Гродно', 'grodno-city', 'grodno'],
            ['Могилёв', 'mogilev-city', 'mogilev'],
            ['Жодино', 'zhodino', 'minsk-region'],
            ['Березино', 'berezino', 'minsk-region'],
            ['Мир', 'mir', 'grodno'],
            ['Мотоль', 'motol', 'brest'],
            ['Чечерск', 'chechersk', 'gomel'],
        ];

        $result = [];
        foreach ($cities as [$name, $slug, $regionSlug]) {
            $city = $this->cityFactory->create($name, $slug, $regionSlug);
            $manager->persist($city);
            $result[$slug] = $city;
        }

        return $result;
    }

    /**
     * @param array<string, City> $citiesBySlug
     *
     * @return array<string, list<District>>
     */
    private function createDistricts(ObjectManager $manager, array $citiesBySlug): array
    {
        $districtsByCity = [];

        foreach (self::CITY_DISTRICTS as $citySlug => $districtNames) {
            $city = $citiesBySlug[$citySlug] ?? null;
            if ($city === null) {
                continue;
            }

            $districtsByCity[$citySlug] = [];
            foreach ($districtNames as $name => $slug) {
                $district = $this->districtFactory->create($city, $name, $slug);
                $manager->persist($district);
                $districtsByCity[$citySlug][] = $district;
            }
        }

        return $districtsByCity;
    }

    /**
     * @return list<MetroStation>
     */
    private function createMetroStations(ObjectManager $manager, City $minsk): array
    {
        $result = [];
        foreach (MetroStationSeedDefinitions::minskStations() as [$name, $slug, $color]) {
            $station = $this->metroStationFactory->create($minsk, $name, $slug, $color);
            $manager->persist($station);
            $result[] = $station;
        }

        return $result;
    }

    /**
     * @param list<User> $owners
     * @param array<string, City> $citiesBySlug
     * @param array<string, list<District>> $districtsByCity
     * @param list<MetroStation> $metroStations
     *
     * @return list<Listing>
     */
    private function createListings(
        ObjectManager $manager,
        array $owners,
        array $citiesBySlug,
        array $districtsByCity,
        array $metroStations,
    ): array {
        $dealTypes = [DealType::Sale, DealType::Rent];
        $listingTypes = [ListingType::Apartment, ListingType::House, ListingType::Room, ListingType::Commercial];
        $typeCombos = [];
        foreach ($dealTypes as $dealTypeOption) {
            foreach ($listingTypes as $listingTypeOption) {
                $typeCombos[] = [$dealTypeOption, $listingTypeOption];
            }
        }
        $globalIndex = 0;
        $ownerCount = count($owners);
        $created = [];

        foreach (self::LISTINGS_PER_CITY as $citySlug => $count) {
            $city = $citiesBySlug[$citySlug] ?? null;
            $districts = $districtsByCity[$citySlug] ?? [];
            if ($city === null || $districts === []) {
                continue;
            }

            [$baseLat, $baseLng] = self::CITY_COORDS[$citySlug] ?? [53.9045, 27.5615];
            $addresses = self::CITY_ADDRESSES[$citySlug] ?? ['ул. Центральная, 1'];
            $priceBase = $citySlug === 'minsk' ? 50_000 : 35_000;
            $priceStep = $citySlug === 'minsk' ? 15_000 : 10_000;
            $isSmallSettlement = in_array($citySlug, ['zhodino', 'berezino', 'mir', 'motol', 'chechersk'], true);
            if ($isSmallSettlement) {
                $priceBase = 18_000;
                $priceStep = 5_000;
            }

            for ($i = 0; $i < $count; ++$i) {
                $owner = $owners[$globalIndex % $ownerCount];
                $district = $districts[$i % count($districts)];
                [$dealType, $listingType] = $typeCombos[$globalIndex % count($typeCombos)];
                $rooms = ($globalIndex % 4) + 1;
                $area = 30.0 + ($globalIndex * 2.5);
                $price = (int) ($priceBase + ($i * $priceStep) + ($rooms * 8_000));

                $metro = $citySlug === 'minsk' ? $metroStations[$globalIndex % count($metroStations)] : null;

                foreach ([true] as $isTest) {
                    $listing = $this->listingFactory->create($owner, $city, $district, $dealType, $listingType, $metro, $isTest);

                    $listing
                        ->setStatus(ListingStatus::Published)
                        ->setIsTest($isTest)
                        ->setPrice($price)
                        ->setPricePerSqm((int) round($price / $area))
                        ->setRooms($rooms)
                        ->setArea($area)
                        ->setFloor(($globalIndex % 16) + 1)
                        ->setTotalFloors(16)
                        ->setAddress($addresses[$i % count($addresses)])
                        ->setLatitude($baseLat + sin($i * 0.9 + $globalIndex * 0.17) * 0.0028)
                        ->setLongitude($baseLng + cos($i * 1.1 + $globalIndex * 0.13) * 0.0032)
                        ->setMetroMinutes($metro !== null ? ($globalIndex % 20) + 1 : null)
                        ->setVerified($globalIndex % 5 === 0)
                        ->setAiGoodPrice($globalIndex % 7 === 0)
                        ->setViews($globalIndex * 3)
                        ->setContactOpens(max(1, (int) floor(($globalIndex * 3) * 0.08)))
                        ->setMessages(max(0, (int) floor(($globalIndex * 3) * 0.04)))
                        ->setImages($this->imageCatalog->forIndex($globalIndex))
                        ->setPublishedAt(new \DateTimeImmutable('-' . $globalIndex . ' days'));

                    $manager->persist($listing);
                    $created[] = $listing;
                }
                ++$globalIndex;
            }
        }

        return $created;
    }

    /**
     * @param list<User> $owners
     * @param list<District> $districts
     * @param list<MetroStation> $metroStations
     *
     * @return list<Listing>
     */
    private function createRentCatalogListings(
        ObjectManager $manager,
        array $owners,
        City $minsk,
        array $districts,
        array $metroStations,
    ): array {
        [$baseLat, $baseLng] = self::CITY_COORDS['minsk'];
        $ownerCount = count($owners);
        $created = [];

        foreach (self::RENT_CATALOG_SEED as $index => $row) {
            [
                $listingTypeValue,
                $rentTermValue,
                $price,
                $rooms,
                $area,
                $floor,
                $hasDeposit,
                $utilitiesIncluded,
                $noCommission,
                $verified,
                $address,
            ] = $row;

            $owner = $owners[$index % $ownerCount];
            $district = $districts[$index % count($districts)];
            $metro = $metroStations[$index % count($metroStations)];

            foreach ([true] as $isTest) {
                $listing = $this->listingFactory->create(
                    $owner,
                    $minsk,
                    $district,
                    DealType::Rent,
                    ListingType::from($listingTypeValue),
                    $metro,
                    $isTest,
                );

                $listing
                    ->setStatus(ListingStatus::Published)
                    ->setIsTest($isTest)
                    ->setRentTerm(RentTerm::from($rentTermValue))
                    ->setHasDeposit($hasDeposit)
                    ->setUtilitiesIncluded($utilitiesIncluded)
                    ->setNoCommission($noCommission)
                    ->setPrice($price)
                    ->setPricePerSqm((int) round($price / $area))
                    ->setRooms($rooms)
                    ->setArea($area)
                    ->setFloor($floor)
                    ->setTotalFloors(16)
                    ->setAddress($address)
                    ->setLatitude($baseLat + sin($index * 0.7) * 0.0025)
                    ->setLongitude($baseLng + cos($index * 0.8) * 0.003)
                    ->setMetroMinutes(($index % 15) + 3)
                    ->setVerified($verified)
                    ->setAiGoodPrice($index % 4 === 0)
                    ->setViews(40 + $index * 7)
                    ->setContactOpens(max(1, (int) floor((40 + $index * 7) * 0.08)))
                    ->setMessages(max(0, (int) floor((40 + $index * 7) * 0.04)))
                    ->setImages($this->imageCatalog->forIndex($index + 20))
                    ->setPublishedAt(new \DateTimeImmutable('-' . ($index + 1) . ' hours'));

                $manager->persist($listing);
                $created[] = $listing;
            }
        }

        return $created;
    }

    /**
     * @param list<User> $owners
     * @param list<District> $districts
     * @param list<MetroStation> $metroStations
     *
     * @return list<Listing>
     */
    private function createSaleCatalogListings(
        ObjectManager $manager,
        array $owners,
        City $minsk,
        array $districts,
        array $metroStations,
    ): array {
        [$baseLat, $baseLng] = self::CITY_COORDS['minsk'];
        $ownerCount = count($owners);
        $created = [];

        foreach (self::SALE_CATALOG_SEED as $index => $row) {
            [
                $listingTypeValue,
                $price,
                $rooms,
                $area,
                $floor,
                $noCommission,
                $fromOwner,
                $hasRenovation,
                $verified,
                $address,
            ] = $row;

            $owner = $owners[$index % $ownerCount];
            $district = $districts[$index % count($districts)];
            $metro = $metroStations[$index % count($metroStations)];

            foreach ([true] as $isTest) {
                $listing = $this->listingFactory->create(
                    $owner,
                    $minsk,
                    $district,
                    DealType::Sale,
                    ListingType::from($listingTypeValue),
                    $metro,
                    $isTest,
                );

                $listing
                    ->setStatus(ListingStatus::Published)
                    ->setIsTest($isTest)
                    ->setNoCommission($noCommission)
                    ->setFromOwner($fromOwner)
                    ->setHasRenovation($hasRenovation)
                    ->setPrice($price)
                    ->setPricePerSqm((int) round($price / $area))
                    ->setRooms($rooms)
                    ->setArea($area)
                    ->setFloor($floor)
                    ->setTotalFloors(16)
                    ->setAddress($address)
                    ->setLatitude($baseLat + sin($index * 0.65) * 0.0025)
                    ->setLongitude($baseLng + cos($index * 0.75) * 0.003)
                    ->setMetroMinutes(($index % 15) + 3)
                    ->setVerified($verified)
                    ->setAiGoodPrice($index % 4 === 0)
                    ->setViews(60 + $index * 9)
                    ->setContactOpens(max(1, (int) floor((60 + $index * 9) * 0.08)))
                    ->setMessages(max(0, (int) floor((60 + $index * 9) * 0.04)))
                    ->setImages($this->imageCatalog->forIndex($index + 40))
                    ->setPublishedAt(new \DateTimeImmutable('-' . ($index + 1) . ' hours'));

                $manager->persist($listing);
                $created[] = $listing;
            }
        }

        return $created;
    }

    /**
     * @param list<Listing> $listings
     * @param array<string, User> $users
     */
    private function createListingRequests(ObjectManager $manager, array $listings, array $users): void
    {
        if ($listings === []) {
            return;
        }

        $definitions = ListingRequestSeedDefinitions::all();
        foreach ($listings as $index => $listing) {
            $definition = $definitions[$index % count($definitions)];
            $requesterEmail = $definition['requesterEmail'];
            $requester = $requesterEmail !== null ? ($users[$requesterEmail] ?? null) : null;

            $manager->persist($this->listingRequestFactory->create(
                $listing,
                $definition['phone'],
                $definition['message'],
                $definition['name'],
                $requester,
                ListingRequestStatus::from($definition['status']),
                $listing->isTest(),
            ));
        }
    }

    /**
     * @param list<Listing> $listings
     */
    private function createListingReports(ObjectManager $manager, array $listings): void
    {
        if ($listings === []) {
            return;
        }

        $definitions = ListingReportSeedDefinitions::all();
        foreach ($listings as $index => $listing) {
            $definition = $definitions[$index % count($definitions)];

            $manager->persist($this->listingReportFactory->create(
                $listing,
                ListingReportReason::from($definition['reason']),
                $definition['comment'],
                ListingReportStatus::from($definition['status']),
                $listing->isTest(),
            ));
        }
    }

    private function createInfoPages(ObjectManager $manager): void
    {
        foreach ([false, true] as $isTest) {
            foreach (InfoPageSeedDefinitions::pages() as $definition) {
                $page = $this->infoPageFactory->create(
                    slug: $definition['slug'],
                    title: $definition['title'],
                    body: $definition['body'],
                    category: $definition['category'],
                    importantNote: $definition['importantNote'],
                    faqItems: $definition['faqItems'],
                    sortOrder: $definition['sortOrder'],
                    isTest: $isTest,
                );
                $page->setUpdatedAt(new \DateTimeImmutable(InfoPageSeedDefinitions::UPDATED_AT));
                $manager->persist($page);
            }
        }
    }

    private function createArticles(ObjectManager $manager): void
    {
        foreach ([true] as $isTest) {
            foreach (ArticleSeedDefinitions::articles() as $index => $definition) {
                $publishedAt = new \DateTimeImmutable($definition['publishedAt']);
                $article = $this->articleFactory->create(
                    slug: $definition['slug'],
                    title: $definition['title'],
                    excerpt: $definition['excerpt'],
                    body: $definition['body'],
                    category: $definition['category'],
                    coverImage: $this->articleImageCatalog->coverForIndex($index),
                    isPublished: true,
                    publishedAt: $publishedAt,
                    metaTitle: $definition['metaTitle'],
                    metaDescription: $definition['metaDescription'],
                    isTest: $isTest,
                    media: $this->articleImageCatalog->galleryForIndex($index),
                );
                $article->setUpdatedAt($publishedAt);
                $manager->persist($article);
            }
        }
    }

    private function createSeoMeta(ObjectManager $manager): void
    {
        foreach ([false, true] as $isTest) {
            foreach (SeoMetaSeedDefinitions::entries() as $definition) {
                $meta = $this->seoMetaFactory->create(
                    pageKey: $definition['pageKey'],
                    locale: $definition['locale'],
                    title: $definition['title'],
                    description: $definition['description'],
                    h1: $definition['h1'],
                    keywords: $definition['keywords'],
                    isTest: $isTest,
                );
                $manager->persist($meta);
            }
        }
    }

    private function createTariffs(ObjectManager $manager): void
    {
        foreach ([false, true] as $isTest) {
            foreach (TariffSeedDefinitions::all() as $definition) {
                $manager->persist($this->tariffFactory->create(
                    code: $definition['code'],
                    priceUsd: $definition['priceUsd'],
                    priceByn: $definition['priceByn'],
                    priceRub: $definition['priceRub'],
                    isPopular: $definition['isPopular'],
                    sortOrder: $definition['sortOrder'],
                    isTest: $isTest,
                ));
            }
        }
    }

    private function createSiteSettings(ObjectManager $manager): void
    {
        $defaults = SiteSettingsSeedDefinitions::defaults();
        foreach ([false, true] as $isTest) {
            $manager->persist($this->siteSettingsFactory->create(
                aboutText: $defaults['aboutText'],
                phoneDisplay: $defaults['phoneDisplay'],
                phoneRaw: $defaults['phoneRaw'],
                email: $defaults['email'],
                supportHours: $defaults['supportHours'],
                ownerName: $defaults['ownerName'],
                address: $defaults['address'],
                offersText: $defaults['offersText'],
                offersEmail: $defaults['offersEmail'],
                telegramUrl: $defaults['telegramUrl'],
                whatsappUrl: $defaults['whatsappUrl'],
                vkUrl: $defaults['vkUrl'],
                isTest: $isTest,
            ));
        }
    }

    private function createMediaFiles(ObjectManager $manager, User $user): void
    {
        $projectDir = dirname(__DIR__, 2);
        $relative = '/uploads/avatars/2026/07/seed-avatar.jpg';
        $absoluteDir = $projectDir . '/public/uploads/avatars/2026/07';
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }
        $absolute = $projectDir . '/public' . $relative;
        if (!is_file($absolute)) {
            file_put_contents($absolute, base64_decode(
                '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxISEhUSEhIVFhUVFRUVFRUVFRUWFxUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAbAAACAwEBAQAAAAAAAAAAAAADBAECBQYAB//EADYQAAIBAwMCBAMFBQEAAAAAAAECAwAEEQUSITFBEyJRYXGBBjKRoRQjQrHB0fAVYnLh8SQz/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAIhEAAgICAgIDAQEAAAAAAAAAAAECERIhAzFBUQQiYZHwMv/aAAwDAQACEQMRAD8A9o0rSlKUAFFFFABRRRQAUUUUAFFFFAH/2Q==',
                true,
            ));
        }

        foreach ([false, true] as $isTest) {
            $url = $isTest
                ? '/uploads/avatars/2026/07/seed-avatar-test.jpg'
                : $relative;
            if ($isTest) {
                $testPath = $projectDir . '/public' . $url;
                if (!is_file($testPath)) {
                    copy($absolute, $testPath);
                }
            }
            $manager->persist($this->mediaFileFactory->create(
                url: $url,
                type: 'image',
                mimeType: 'image/jpeg',
                size: is_file($projectDir . '/public' . $url) ? (int) filesize($projectDir . '/public' . $url) : 100,
                context: 'avatar',
                uploadedBy: $user,
                originalName: 'avatar.jpg',
                isTest: $isTest,
            ));
        }
    }

    private function createAiPreferences(ObjectManager $manager, User $user): void
    {
        foreach (AiPreferenceSeedDefinitions::entries() as $definition) {
            $manager->persist($this->aiPreferenceFactory->create(
                user: $user,
                answers: $definition['answers'],
                filters: $definition['filters'],
                recommendedListingIds: [],
                summary: $definition['summary'],
                highlights: $definition['highlights'],
                isTest: true,
            ));
        }
    }
}
