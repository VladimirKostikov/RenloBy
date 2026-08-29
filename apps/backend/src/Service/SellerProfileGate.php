<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\ApiErrorCode;

class SellerProfileGate
{
    public function assertComplete(User $user): void
    {
        $fields = $this->missingFields($user);
        if ($fields === []) {
            return;
        }

        throw new ValidationException($fields, ApiErrorCode::VALIDATION_PROFILE_INCOMPLETE);
    }

    /**
     * @return array<string, string>
     */
    public function missingFields(User $user): array
    {
        $fields = [];

        if (!$this->filled($user->getLastName())) {
            $fields['lastName'] = ApiErrorCode::VALIDATION_PROFILE_LAST_NAME;
        }
        if (!$this->filled($user->getFirstName())) {
            $fields['firstName'] = ApiErrorCode::VALIDATION_PROFILE_FIRST_NAME;
        }
        if (!$this->filled($user->getPatronymic())) {
            $fields['patronymic'] = ApiErrorCode::VALIDATION_PROFILE_PATRONYMIC;
        }
        if (!$this->hasSocial($user)) {
            $fields['social'] = ApiErrorCode::VALIDATION_PROFILE_SOCIAL_REQUIRED;
        }

        return $fields;
    }

    public function isComplete(User $user): bool
    {
        return $this->missingFields($user) === [];
    }

    private function hasSocial(User $user): bool
    {
        return $this->filled($user->getInstagram())
            || $this->filled($user->getTelegram())
            || $this->filled($user->getWhatsapp())
            || $this->filled($user->getViber());
    }

    private function filled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
