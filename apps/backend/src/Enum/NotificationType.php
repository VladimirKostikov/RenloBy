<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationType: string
{
    case ListingStatusChanged = 'listing_status_changed';
    case ListingContactRequestCreated = 'listing_contact_request_created';
}
