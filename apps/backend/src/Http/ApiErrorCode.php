<?php

declare(strict_types=1);

namespace App\Http;

final class ApiErrorCode
{
    public const AUTH_INVALID_CREDENTIALS = 'auth.invalid_credentials';
    public const AUTH_INVALID_USER = 'auth.invalid_user';
    public const AUTH_EMAIL_EXISTS = 'auth.email_exists';
    public const AUTH_RATE_LIMITED = 'auth.rate_limited';
    public const WEBHOOK_FORBIDDEN = 'webhook.forbidden';

    public const VALIDATION_FAILED = 'validation.failed';
    public const VALIDATION_EMAIL_REQUIRED = 'validation.email_required';
    public const VALIDATION_EMAIL_INVALID = 'validation.email_invalid';
    public const VALIDATION_PASSWORD_REQUIRED = 'validation.password_required';
    public const VALIDATION_PASSWORD_MIN = 'validation.password_min';
    public const VALIDATION_NAME_REQUIRED = 'validation.name_required';
    public const VALIDATION_NAME_LENGTH = 'validation.name_length';
    public const VALIDATION_PHONE_INVALID = 'validation.phone_invalid';
    public const VALIDATION_PHOTO_INVALID = 'validation.photo_invalid';
    public const VALIDATION_SOCIAL_INVALID = 'validation.social_invalid';
    public const VALIDATION_LISTING_ID_INVALID = 'validation.listing_id_invalid';
    public const VALIDATION_PROFILE_INCOMPLETE = 'validation.profile_incomplete';
    public const VALIDATION_PROFILE_LAST_NAME = 'validation.profile_last_name';
    public const VALIDATION_PROFILE_FIRST_NAME = 'validation.profile_first_name';
    public const VALIDATION_PROFILE_PATRONYMIC = 'validation.profile_patronymic';
    public const VALIDATION_PROFILE_SOCIAL_REQUIRED = 'validation.profile_social_required';

    public const COMPARISON_LIMIT_REACHED = 'comparison.limit_reached';

    public const FORBIDDEN_LISTING = 'forbidden.listing';
    public const FORBIDDEN_LISTING_REQUEST = 'forbidden.listing_request';

    public const VALIDATION_LISTING_DEAL_TYPE = 'validation.listing.deal_type';
    public const VALIDATION_LISTING_TYPE = 'validation.listing.type';
    public const VALIDATION_LISTING_PRICE = 'validation.listing.price';
    public const VALIDATION_LISTING_ROOMS = 'validation.listing.rooms';
    public const VALIDATION_LISTING_AREA = 'validation.listing.area';
    public const VALIDATION_LISTING_FLOOR = 'validation.listing.floor';
    public const VALIDATION_LISTING_TOTAL_FLOORS = 'validation.listing.total_floors';
    public const VALIDATION_LISTING_ADDRESS = 'validation.listing.address';
    public const VALIDATION_LISTING_CITY = 'validation.listing.city';
    public const VALIDATION_LISTING_DISTRICT = 'validation.listing.district';
    public const VALIDATION_LISTING_COORDS = 'validation.listing.coords';
    public const VALIDATION_LISTING_IMAGES = 'validation.listing.images';

    public const NOT_FOUND_LISTING = 'not_found.listing';
    public const NOT_FOUND_FAVORITE = 'not_found.favorite';
    public const NOT_FOUND_COMPARISON = 'not_found.comparison';
    public const NOT_FOUND_SAVED_SEARCH = 'not_found.saved_search';
    public const NOT_FOUND_USER = 'not_found.user';
    public const NOT_FOUND_CITY = 'not_found.city';
    public const NOT_FOUND_DISTRICT = 'not_found.district';
    public const NOT_FOUND_METRO_STATION = 'not_found.metro_station';
    public const NOT_FOUND_INFO_PAGE = 'not_found.info_page';
    public const NOT_FOUND_ARTICLE = 'not_found.article';
    public const NOT_FOUND_SEO_META = 'not_found.seo_meta';
    public const NOT_FOUND_PAYMENT_TRANSACTION = 'not_found.payment_transaction';
    public const NOT_FOUND_LISTING_REPORT = 'not_found.listing_report';
    public const NOT_FOUND_LISTING_REQUEST = 'not_found.listing_request';
    public const NOT_FOUND_TELEGRAM_SUBSCRIBER = 'not_found.telegram_subscriber';
    public const NOT_FOUND_TARIFF = 'not_found.tariff';
    public const NOT_FOUND_SITE_SETTINGS = 'not_found.site_settings';
    public const NOT_FOUND_HEAD_SNIPPET = 'not_found.head_snippet';
    public const NOT_FOUND_MEDIA_FILE = 'not_found.media_file';
    public const NOT_FOUND_NOTIFICATION = 'not_found.notification';
    public const NOT_FOUND_AI_PREFERENCE = 'not_found.ai_preference';

    public const AI_CHAT_UNAVAILABLE = 'ai_chat.unavailable';
}
