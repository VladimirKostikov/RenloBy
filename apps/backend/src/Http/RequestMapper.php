<?php

declare(strict_types=1);

namespace App\Http;

use App\Dto\AiAssistant\CreateAiPreferenceRequest;
use App\Dto\AiChat\AiChatHistoryItem;
use App\Dto\AiChat\AiChatRequest;
use App\Dto\Auth\RegisterRequest;
use App\Dto\Auth\UpdateProfileRequest;
use App\Dto\City\CreateCityRequest;
use App\Dto\City\UpdateCityRequest;
use App\Dto\Comparison\CreateComparisonRequest;
use App\Dto\District\CreateDistrictRequest;
use App\Dto\District\UpdateDistrictRequest;
use App\Dto\Favorite\CreateFavoriteRequest;
use App\Dto\Article\CreateArticleRequest;
use App\Dto\Article\UpdateArticleRequest;
use App\Dto\InfoPage\CreateInfoPageRequest;
use App\Dto\InfoPage\UpdateInfoPageRequest;
use App\Dto\SeoMeta\CreateSeoMetaRequest;
use App\Dto\SeoMeta\UpdateSeoMetaRequest;
use App\Dto\Listing\CreateListingRequest;
use App\Dto\Listing\CreateSellerListingRequest;
use App\Dto\Listing\ListingSearchRequest;
use App\Dto\Listing\UpdateListingRequest;
use App\Dto\Listing\UpdateSellerListingRequest;
use App\Dto\MetroStation\CreateMetroStationRequest;
use App\Dto\MetroStation\UpdateMetroStationRequest;
use App\Dto\SavedSearch\CreateSavedSearchRequest;
use App\Dto\SavedSearch\UpdateSavedSearchRequest;
use App\Dto\User\CreateUserRequest;
use App\Dto\User\UpdateUserRequest;
use App\Dto\ListingReport\CreateListingReportRequest;
use App\Dto\ListingReport\UpdateListingReportRequest;
use App\Dto\ListingRequest\CreateListingRequestRequest;
use App\Dto\ListingRequest\UpdateListingRequestRequest;
use App\Dto\Tariff\UpdateTariffRequest;
use App\Dto\SiteSettings\UpdateSiteSettingsRequest;
use App\Dto\HeadSnippet\CreateHeadSnippetRequest;
use App\Dto\HeadSnippet\UpdateHeadSnippetRequest;
use App\Enum\ArticleCategory;
use App\Enum\DealType;
use App\Enum\InfoPageCategory;
use App\Enum\ListingReportReason;
use App\Enum\ListingReportStatus;
use App\Enum\ListingRequestStatus;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class RequestMapper
{
    public function mapListingSearch(HttpRequest $request): ListingSearchRequest
    {
        $dealType = $request->query->get('dealType');
        $listingType = $request->query->get('listingType');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'publishedAt');

        $rentTerm = $request->query->get('rentTerm');
        $query = $request->query->get('query');
        $allowedSorts = ['publishedAt', 'price', 'area', 'views', 'random'];
        $sortValue = is_string($sort) && in_array($sort, $allowedSorts, true) ? $sort : 'publishedAt';

        return new ListingSearchRequest(
            dealType: is_string($dealType) && $dealType !== '' ? DealType::from($dealType) : null,
            listingType: is_string($listingType) && $listingType !== '' ? ListingType::from($listingType) : null,
            status: is_string($status) && $status !== '' ? ListingStatus::from($status) : null,
            cityId: $this->nullableInt($request->query->get('cityId')),
            regionSlug: $this->nullableString($request->query->get('regionSlug')),
            districtId: $this->nullableInt($request->query->get('districtId')),
            rooms: $this->nullableInt($request->query->get('rooms')),
            floor: $this->nullableInt($request->query->get('floor')),
            minArea: $this->nullableFloat($request->query->get('minArea')),
            maxArea: $this->nullableFloat($request->query->get('maxArea')),
            minPrice: $this->nullableInt($request->query->get('minPrice')),
            maxPrice: $this->nullableInt($request->query->get('maxPrice')),
            verified: $this->nullableBool($request->query->get('verified')),
            rentTerm: is_string($rentTerm) && $rentTerm !== '' ? RentTerm::from($rentTerm) : null,
            hasDeposit: $this->nullableBool($request->query->get('hasDeposit')),
            utilitiesIncluded: $this->nullableBool($request->query->get('utilitiesIncluded')),
            noCommission: $this->nullableBool($request->query->get('noCommission')),
            fromOwner: $this->nullableBool($request->query->get('fromOwner')),
            hasRenovation: $this->nullableBool($request->query->get('hasRenovation')),
            query: is_string($query) && trim($query) !== '' ? trim($query) : null,
            sort: $sortValue,
            direction: (string) $request->query->get('direction', 'DESC'),
            page: max(1, (int) $request->query->get('page', 1)),
            limit: max(1, min(100, (int) $request->query->get('limit', 20))),
        );
    }

    public function mapCreateSellerListing(array $data): CreateSellerListingRequest
    {
        $rentTerm = $data['rentTerm'] ?? null;
        $status = $data['status'] ?? ListingStatus::Draft->value;
        $images = is_array($data['images'] ?? null) ? array_values($data['images']) : [];

        return new CreateSellerListingRequest(
            dealType: DealType::from((string) ($data['dealType'] ?? '')),
            listingType: ListingType::from((string) ($data['listingType'] ?? '')),
            price: (int) ($data['price'] ?? 0),
            rooms: (int) ($data['rooms'] ?? 0),
            area: (float) ($data['area'] ?? 0),
            floor: $this->nullableInt($data['floor'] ?? null),
            totalFloors: $this->nullableInt($data['totalFloors'] ?? null),
            address: trim((string) ($data['address'] ?? '')),
            latitude: (float) ($data['latitude'] ?? 0),
            longitude: (float) ($data['longitude'] ?? 0),
            city: trim((string) ($data['city'] ?? '')),
            district: $this->nullableString($data['district'] ?? null),
            metro: $this->nullableString($data['metro'] ?? null),
            metroLineColor: $this->nullableString($data['metroLineColor'] ?? null),
            metroMinutes: $this->nullableInt($data['metroMinutes'] ?? null),
            rentTerm: is_string($rentTerm) && $rentTerm !== '' ? RentTerm::from($rentTerm) : null,
            hasDeposit: (bool) ($data['hasDeposit'] ?? false),
            utilitiesIncluded: (bool) ($data['utilitiesIncluded'] ?? false),
            noCommission: (bool) ($data['noCommission'] ?? false),
            fromOwner: (bool) ($data['fromOwner'] ?? true),
            hasRenovation: (bool) ($data['hasRenovation'] ?? false),
            priceNegotiable: (bool) ($data['priceNegotiable'] ?? false),
            images: $images,
            status: is_string($status) && $status !== '' ? ListingStatus::from($status) : ListingStatus::Draft,
            metaTitle: array_key_exists('metaTitle', $data) ? $this->nullableString($data['metaTitle']) : null,
            metaDescription: array_key_exists('metaDescription', $data) ? $this->nullableString($data['metaDescription']) : null,
            metaKeywords: array_key_exists('metaKeywords', $data) ? $this->nullableString($data['metaKeywords']) : null,
        );
    }

    public function mapUpdateSellerListing(array $data): UpdateSellerListingRequest
    {
        $rentTerm = $data['rentTerm'] ?? null;
        $status = $data['status'] ?? null;
        $images = array_key_exists('images', $data) && is_array($data['images'])
            ? array_values($data['images'])
            : null;
        $metroProvided = array_key_exists('metro', $data);
        $metro = $metroProvided ? $this->nullableString($data['metro']) : null;
        $floorProvided = array_key_exists('floor', $data);
        $floor = $floorProvided ? $this->nullableInt($data['floor']) : null;
        $totalFloorsProvided = array_key_exists('totalFloors', $data);
        $totalFloors = $totalFloorsProvided ? $this->nullableInt($data['totalFloors']) : null;
        $metroMinutesProvided = array_key_exists('metroMinutes', $data);
        $metroMinutes = $metroMinutesProvided ? $this->nullableInt($data['metroMinutes']) : null;
        $metaTitleProvided = array_key_exists('metaTitle', $data);
        $metaDescriptionProvided = array_key_exists('metaDescription', $data);
        $metaKeywordsProvided = array_key_exists('metaKeywords', $data);

        return new UpdateSellerListingRequest(
            dealType: isset($data['dealType']) ? DealType::from((string) $data['dealType']) : null,
            listingType: isset($data['listingType']) ? ListingType::from((string) $data['listingType']) : null,
            price: isset($data['price']) ? (int) $data['price'] : null,
            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            area: isset($data['area']) ? (float) $data['area'] : null,
            floor: $floor,
            clearFloor: $floorProvided && $floor === null,
            totalFloors: $totalFloors,
            clearTotalFloors: $totalFloorsProvided && $totalFloors === null,
            address: isset($data['address']) ? trim((string) $data['address']) : null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            city: isset($data['city']) ? trim((string) $data['city']) : null,
            district: isset($data['district']) ? trim((string) $data['district']) : null,
            metro: $metro,
            clearMetro: $metroProvided && ($metro === null || $metro === ''),
            metroLineColor: array_key_exists('metroLineColor', $data) ? $this->nullableString($data['metroLineColor']) : null,
            metroMinutes: $metroMinutes,
            clearMetroMinutes: $metroMinutesProvided && $metroMinutes === null,
            rentTerm: is_string($rentTerm) && $rentTerm !== '' ? RentTerm::from($rentTerm) : null,
            hasDeposit: isset($data['hasDeposit']) ? (bool) $data['hasDeposit'] : null,
            utilitiesIncluded: isset($data['utilitiesIncluded']) ? (bool) $data['utilitiesIncluded'] : null,
            noCommission: isset($data['noCommission']) ? (bool) $data['noCommission'] : null,
            fromOwner: isset($data['fromOwner']) ? (bool) $data['fromOwner'] : null,
            hasRenovation: isset($data['hasRenovation']) ? (bool) $data['hasRenovation'] : null,
            priceNegotiable: isset($data['priceNegotiable']) ? (bool) $data['priceNegotiable'] : null,
            images: $images,
            status: is_string($status) && $status !== '' ? ListingStatus::from($status) : null,
            metaTitle: $metaTitleProvided ? $this->nullableString($data['metaTitle']) : null,
            metaTitleProvided: $metaTitleProvided,
            metaDescription: $metaDescriptionProvided ? $this->nullableString($data['metaDescription']) : null,
            metaDescriptionProvided: $metaDescriptionProvided,
            metaKeywords: $metaKeywordsProvided ? $this->nullableString($data['metaKeywords']) : null,
            metaKeywordsProvided: $metaKeywordsProvided,
        );
    }

    public function mapCreateListing(array $data): CreateListingRequest
    {
        return new CreateListingRequest(
            dealType: DealType::from((string) $data['dealType']),
            listingType: ListingType::from((string) $data['listingType']),
            price: (int) $data['price'],
            rooms: (int) $data['rooms'],
            area: (float) $data['area'],
            floor: $this->nullableInt($data['floor'] ?? null),
            totalFloors: $this->nullableInt($data['totalFloors'] ?? null),
            address: (string) $data['address'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            city: trim((string) ($data['city'] ?? '')),
            district: trim((string) ($data['district'] ?? '')),
            userId: (int) $data['userId'],
            metro: $this->nullableString($data['metro'] ?? null),
            metroMinutes: $this->nullableInt($data['metroMinutes'] ?? null),
            verified: (bool) ($data['verified'] ?? false),
            aiGoodPrice: (bool) ($data['aiGoodPrice'] ?? false),
            images: is_array($data['images'] ?? null) ? $data['images'] : [],
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateListing(array $data): UpdateListingRequest
    {
        $metroProvided = array_key_exists('metro', $data);
        $metro = $metroProvided ? $this->nullableString($data['metro']) : null;
        $floorProvided = array_key_exists('floor', $data);
        $floor = $floorProvided ? $this->nullableInt($data['floor']) : null;
        $totalFloorsProvided = array_key_exists('totalFloors', $data);
        $totalFloors = $totalFloorsProvided ? $this->nullableInt($data['totalFloors']) : null;
        $metroMinutesProvided = array_key_exists('metroMinutes', $data);
        $metroMinutes = $metroMinutesProvided ? $this->nullableInt($data['metroMinutes']) : null;

        return new UpdateListingRequest(
            dealType: isset($data['dealType']) ? DealType::from((string) $data['dealType']) : null,
            listingType: isset($data['listingType']) ? ListingType::from((string) $data['listingType']) : null,
            price: isset($data['price']) ? (int) $data['price'] : null,
            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            area: isset($data['area']) ? (float) $data['area'] : null,
            floor: $floor,
            clearFloor: $floorProvided && $floor === null,
            totalFloors: $totalFloors,
            clearTotalFloors: $totalFloorsProvided && $totalFloors === null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            city: isset($data['city']) ? trim((string) $data['city']) : null,
            district: isset($data['district']) ? trim((string) $data['district']) : null,
            metro: $metro,
            clearMetro: $metroProvided && ($metro === null || $metro === ''),
            metroMinutes: $metroMinutes,
            clearMetroMinutes: $metroMinutesProvided && $metroMinutes === null,
            verified: isset($data['verified']) ? (bool) $data['verified'] : null,
            aiGoodPrice: isset($data['aiGoodPrice']) ? (bool) $data['aiGoodPrice'] : null,
            images: isset($data['images']) && is_array($data['images']) ? $data['images'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
            status: isset($data['status']) && is_string($data['status']) && $data['status'] !== ''
                ? ListingStatus::from($data['status'])
                : null,
        );
    }

    public function mapCreateCity(array $data): CreateCityRequest
    {
        return new CreateCityRequest(
            (string) $data['name'],
            (string) $data['slug'],
            (string) ($data['regionSlug'] ?? 'minsk-region'),
            array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateCity(array $data): UpdateCityRequest
    {
        return new UpdateCityRequest(
            name: isset($data['name']) ? (string) $data['name'] : null,
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            regionSlug: isset($data['regionSlug']) ? (string) $data['regionSlug'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapCreateDistrict(array $data): CreateDistrictRequest
    {
        return new CreateDistrictRequest(
            (string) $data['name'],
            (string) $data['slug'],
            (int) $data['cityId'],
            array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateDistrict(array $data): UpdateDistrictRequest
    {
        return new UpdateDistrictRequest(
            name: isset($data['name']) ? (string) $data['name'] : null,
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            cityId: isset($data['cityId']) ? (int) $data['cityId'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapCreateMetroStation(array $data): CreateMetroStationRequest
    {
        return new CreateMetroStationRequest(
            (string) $data['name'],
            (string) $data['slug'],
            (string) ($data['lineColor'] ?? '#0072BC'),
            (int) $data['cityId'],
            array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateMetroStation(array $data): UpdateMetroStationRequest
    {
        return new UpdateMetroStationRequest(
            name: isset($data['name']) ? (string) $data['name'] : null,
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            lineColor: isset($data['lineColor']) ? (string) $data['lineColor'] : null,
            cityId: isset($data['cityId']) ? (int) $data['cityId'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapCreateFavorite(array $data): CreateFavoriteRequest
    {
        return new CreateFavoriteRequest((int) $data['listingId']);
    }

    public function mapCreateAiPreference(array $data): CreateAiPreferenceRequest
    {
        $answers = $data['answers'] ?? [];

        return new CreateAiPreferenceRequest(is_array($answers) ? $answers : []);
    }

    public function mapCreateComparison(array $data): CreateComparisonRequest
    {
        return new CreateComparisonRequest((int) $data['listingId']);
    }

    public function mapCreateSavedSearch(array $data): CreateSavedSearchRequest
    {
        return new CreateSavedSearchRequest(
            (string) $data['name'],
            is_array($data['filters'] ?? null) ? $data['filters'] : [],
        );
    }

    public function mapUpdateSavedSearch(array $data): UpdateSavedSearchRequest
    {
        return new UpdateSavedSearchRequest(
            name: isset($data['name']) ? (string) $data['name'] : null,
            filters: isset($data['filters']) && is_array($data['filters']) ? $data['filters'] : null,
        );
    }

    public function mapCreateUser(array $data): CreateUserRequest
    {
        return new CreateUserRequest(
            email: (string) $data['email'],
            password: (string) $data['password'],
            roles: is_array($data['roles'] ?? null) ? $data['roles'] : [],
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
            lastName: array_key_exists('lastName', $data) ? (string) ($data['lastName'] ?? '') : null,
            firstName: array_key_exists('firstName', $data) ? (string) ($data['firstName'] ?? '') : null,
            patronymic: array_key_exists('patronymic', $data) ? (string) ($data['patronymic'] ?? '') : null,
            photo: isset($data['photo']) ? (string) $data['photo'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            instagram: isset($data['instagram']) ? (string) $data['instagram'] : null,
            telegram: isset($data['telegram']) ? (string) $data['telegram'] : null,
            whatsapp: isset($data['whatsapp']) ? (string) $data['whatsapp'] : null,
            viber: isset($data['viber']) ? (string) $data['viber'] : null,
        );
    }

    public function mapRegisterRequest(array $data): RegisterRequest
    {
        return new RegisterRequest(
            email: trim((string) ($data['email'] ?? '')),
            password: (string) ($data['password'] ?? ''),
        );
    }

    public function mapUpdateProfile(array $data): UpdateProfileRequest
    {
        return new UpdateProfileRequest(
            lastName: array_key_exists('lastName', $data) ? (string) ($data['lastName'] ?? '') : null,
            firstName: array_key_exists('firstName', $data) ? (string) ($data['firstName'] ?? '') : null,
            patronymic: array_key_exists('patronymic', $data) ? (string) ($data['patronymic'] ?? '') : null,
            phone: array_key_exists('phone', $data) ? (string) ($data['phone'] ?? '') : null,
            photo: array_key_exists('photo', $data) ? (string) ($data['photo'] ?? '') : null,
            instagram: array_key_exists('instagram', $data) ? (string) ($data['instagram'] ?? '') : null,
            telegram: array_key_exists('telegram', $data) ? (string) ($data['telegram'] ?? '') : null,
            whatsapp: array_key_exists('whatsapp', $data) ? (string) ($data['whatsapp'] ?? '') : null,
            viber: array_key_exists('viber', $data) ? (string) ($data['viber'] ?? '') : null,
        );
    }

    public function mapUpdateUser(array $data): UpdateUserRequest
    {
        $updateNameParts = array_key_exists('lastName', $data)
            || array_key_exists('firstName', $data)
            || array_key_exists('patronymic', $data);

        return new UpdateUserRequest(
            email: isset($data['email']) ? (string) $data['email'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
            roles: isset($data['roles']) && is_array($data['roles']) ? $data['roles'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
            lastName: array_key_exists('lastName', $data) ? (string) ($data['lastName'] ?? '') : null,
            firstName: array_key_exists('firstName', $data) ? (string) ($data['firstName'] ?? '') : null,
            patronymic: array_key_exists('patronymic', $data) ? (string) ($data['patronymic'] ?? '') : null,
            updateNameParts: $updateNameParts,
            photo: array_key_exists('photo', $data) ? (string) ($data['photo'] ?? '') : null,
            phone: array_key_exists('phone', $data) ? (string) ($data['phone'] ?? '') : null,
            instagram: array_key_exists('instagram', $data) ? (string) ($data['instagram'] ?? '') : null,
            telegram: array_key_exists('telegram', $data) ? (string) ($data['telegram'] ?? '') : null,
            whatsapp: array_key_exists('whatsapp', $data) ? (string) ($data['whatsapp'] ?? '') : null,
            viber: array_key_exists('viber', $data) ? (string) ($data['viber'] ?? '') : null,
            clearPhoto: array_key_exists('photo', $data) && trim((string) ($data['photo'] ?? '')) === '',
        );
    }

    public function mapCreateInfoPage(array $data): CreateInfoPageRequest
    {
        return new CreateInfoPageRequest(
            slug: (string) $data['slug'],
            title: (string) $data['title'],
            body: (string) ($data['body'] ?? ''),
            category: InfoPageCategory::from((string) $data['category']),
            importantNote: isset($data['importantNote']) ? (string) $data['importantNote'] : null,
            faqItems: is_array($data['faqItems'] ?? null) ? $data['faqItems'] : [],
            sortOrder: (int) ($data['sortOrder'] ?? 0),
            metaTitle: isset($data['metaTitle']) ? (string) $data['metaTitle'] : null,
            metaDescription: isset($data['metaDescription']) ? (string) $data['metaDescription'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateInfoPage(array $data): UpdateInfoPageRequest
    {
        return new UpdateInfoPageRequest(
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            body: isset($data['body']) ? (string) $data['body'] : null,
            category: isset($data['category']) ? InfoPageCategory::from((string) $data['category']) : null,
            importantNote: array_key_exists('importantNote', $data) ? (string) $data['importantNote'] : null,
            faqItems: isset($data['faqItems']) && is_array($data['faqItems']) ? $data['faqItems'] : null,
            sortOrder: isset($data['sortOrder']) ? (int) $data['sortOrder'] : null,
            metaTitle: array_key_exists('metaTitle', $data) ? (string) $data['metaTitle'] : null,
            metaDescription: array_key_exists('metaDescription', $data) ? (string) $data['metaDescription'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapCreateArticle(array $data): CreateArticleRequest
    {
        return new CreateArticleRequest(
            slug: (string) $data['slug'],
            title: (string) $data['title'],
            excerpt: (string) ($data['excerpt'] ?? ''),
            body: (string) ($data['body'] ?? ''),
            category: ArticleCategory::from((string) $data['category']),
            coverImage: isset($data['coverImage']) ? (string) $data['coverImage'] : null,
            isPublished: (bool) ($data['isPublished'] ?? false),
            publishedAt: (string) ($data['publishedAt'] ?? (new \DateTimeImmutable())->format('Y-m-d')),
            metaTitle: isset($data['metaTitle']) ? (string) $data['metaTitle'] : null,
            metaDescription: isset($data['metaDescription']) ? (string) $data['metaDescription'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
            media: isset($data['media']) && is_array($data['media']) ? $data['media'] : null,
        );
    }

    public function mapUpdateArticle(array $data): UpdateArticleRequest
    {
        return new UpdateArticleRequest(
            slug: isset($data['slug']) ? (string) $data['slug'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            excerpt: isset($data['excerpt']) ? (string) $data['excerpt'] : null,
            body: isset($data['body']) ? (string) $data['body'] : null,
            category: isset($data['category']) ? ArticleCategory::from((string) $data['category']) : null,
            coverImage: array_key_exists('coverImage', $data) ? (string) $data['coverImage'] : null,
            isPublished: array_key_exists('isPublished', $data) ? (bool) $data['isPublished'] : null,
            publishedAt: isset($data['publishedAt']) ? (string) $data['publishedAt'] : null,
            metaTitle: array_key_exists('metaTitle', $data) ? (string) $data['metaTitle'] : null,
            metaDescription: array_key_exists('metaDescription', $data) ? (string) $data['metaDescription'] : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
            media: array_key_exists('media', $data) && is_array($data['media']) ? $data['media'] : null,
        );
    }

    public function mapCreateSeoMeta(array $data): CreateSeoMetaRequest
    {
        return new CreateSeoMetaRequest(
            pageKey: (string) $data['pageKey'],
            locale: (string) ($data['locale'] ?? 'ru'),
            title: (string) $data['title'],
            description: (string) ($data['description'] ?? ''),
            h1: isset($data['h1']) ? (string) $data['h1'] : null,
            keywords: array_key_exists('keywords', $data) ? (string) ($data['keywords'] ?? '') : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateSeoMeta(array $data): UpdateSeoMetaRequest
    {
        return new UpdateSeoMetaRequest(
            pageKey: isset($data['pageKey']) ? (string) $data['pageKey'] : null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            h1: array_key_exists('h1', $data) ? (string) $data['h1'] : null,
            keywords: array_key_exists('keywords', $data) ? (string) ($data['keywords'] ?? '') : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapCreateListingReport(array $data): CreateListingReportRequest
    {
        $reason = ListingReportReason::tryFrom((string) ($data['reason'] ?? ''));
        if ($reason === null) {
            throw new ValidationException(['reason' => ApiErrorCode::VALIDATION_FAILED]);
        }

        return new CreateListingReportRequest(
            reason: $reason,
            comment: trim((string) ($data['comment'] ?? '')),
        );
    }

    public function mapUpdateListingReport(array $data): UpdateListingReportRequest
    {
        $statusRaw = $data['status'] ?? null;
        $status = null;
        if (is_string($statusRaw) && $statusRaw !== '') {
            $status = ListingReportStatus::tryFrom($statusRaw);
            if ($status === null) {
                throw new ValidationException(['status' => ApiErrorCode::VALIDATION_FAILED]);
            }
        }

        return new UpdateListingReportRequest(status: $status);
    }

    public function mapCreateListingRequest(array $data): CreateListingRequestRequest
    {
        $nameRaw = $data['name'] ?? null;
        $name = null;
        if (is_string($nameRaw) && trim($nameRaw) !== '') {
            $name = trim($nameRaw);
        }

        return new CreateListingRequestRequest(
            phone: trim((string) ($data['phone'] ?? '')),
            message: trim((string) ($data['message'] ?? '')),
            name: $name,
        );
    }

    public function mapUpdateListingRequest(array $data): UpdateListingRequestRequest
    {
        $statusRaw = $data['status'] ?? null;
        $status = null;
        if (is_string($statusRaw) && $statusRaw !== '') {
            $status = ListingRequestStatus::tryFrom($statusRaw);
            if ($status === null) {
                throw new ValidationException(['status' => ApiErrorCode::VALIDATION_FAILED]);
            }
        }

        return new UpdateListingRequestRequest(status: $status);
    }

    public function mapUpdateTariff(array $data): UpdateTariffRequest
    {
        return new UpdateTariffRequest(
            priceUsd: $this->nullableString($data['priceUsd'] ?? null),
            priceByn: $this->nullableString($data['priceByn'] ?? null),
            priceRub: $this->nullableString($data['priceRub'] ?? null),
            currency: $this->nullableString($data['currency'] ?? null),
            isPopular: array_key_exists('isPopular', $data) ? (bool) $data['isPopular'] : null,
            sortOrder: array_key_exists('sortOrder', $data) ? $this->nullableInt($data['sortOrder']) : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateSiteSettings(array $data): UpdateSiteSettingsRequest
    {
        return new UpdateSiteSettingsRequest(
            aboutText: $this->nullableString($data['aboutText'] ?? null),
            phoneDisplay: $this->nullableString($data['phoneDisplay'] ?? null),
            phoneRaw: $this->nullableString($data['phoneRaw'] ?? null),
            email: $this->nullableString($data['email'] ?? null),
            supportHours: $this->nullableString($data['supportHours'] ?? null),
            ownerName: array_key_exists('ownerName', $data) ? (string) ($data['ownerName'] ?? '') : null,
            address: array_key_exists('address', $data) ? (string) ($data['address'] ?? '') : null,
            offersText: array_key_exists('offersText', $data) ? (string) ($data['offersText'] ?? '') : null,
            offersEmail: array_key_exists('offersEmail', $data) ? (string) ($data['offersEmail'] ?? '') : null,
            telegramUrl: array_key_exists('telegramUrl', $data) ? (string) ($data['telegramUrl'] ?? '') : null,
            whatsappUrl: array_key_exists('whatsappUrl', $data) ? (string) ($data['whatsappUrl'] ?? '') : null,
            vkUrl: array_key_exists('vkUrl', $data) ? (string) ($data['vkUrl'] ?? '') : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapAiChat(array $data): AiChatRequest
    {
        $history = [];
        $rawHistory = $data['history'] ?? [];
        if (is_array($rawHistory)) {
            foreach ($rawHistory as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $role = isset($item['role']) && is_string($item['role']) ? trim($item['role']) : '';
                $content = isset($item['content']) && is_string($item['content']) ? trim($item['content']) : '';
                $history[] = new AiChatHistoryItem($role, $content);
            }
        }

        $message = isset($data['message']) && is_string($data['message']) ? trim($data['message']) : '';
        $locale = isset($data['locale']) && is_string($data['locale']) ? strtolower(trim($data['locale'])) : 'ru';
        if ($locale !== 'en' && $locale !== 'ru') {
            $locale = 'ru';
        }

        return new AiChatRequest($message, $history, $locale);
    }

    public function mapCreateHeadSnippet(array $data): CreateHeadSnippetRequest
    {
        return new CreateHeadSnippetRequest(
            name: (string) ($data['name'] ?? ''),
            code: (string) ($data['code'] ?? ''),
            isEnabled: array_key_exists('isEnabled', $data) ? (bool) $data['isEnabled'] : true,
            sortOrder: isset($data['sortOrder']) ? (int) $data['sortOrder'] : 0,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function mapUpdateHeadSnippet(array $data): UpdateHeadSnippetRequest
    {
        return new UpdateHeadSnippetRequest(
            name: $this->nullableString($data['name'] ?? null),
            code: $this->nullableString($data['code'] ?? null),
            isEnabled: array_key_exists('isEnabled', $data) ? (bool) $data['isEnabled'] : null,
            sortOrder: array_key_exists('sortOrder', $data) ? $this->nullableInt($data['sortOrder']) : null,
            isTest: array_key_exists('isTest', $data) ? (bool) $data['isTest'] : null,
        );
    }

    public function decodeJson(HttpRequest $request): array
    {
        $data = json_decode($request->getContent(), true);

        return is_array($data) ? $data : [];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower((string) $value);

        return match ($normalized) {
            '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => null,
        };
    }
}
