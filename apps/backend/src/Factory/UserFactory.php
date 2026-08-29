<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;

class UserFactory
{
    public function create(
        string $email = 'user@renlo.local',
        string $name = '',
        array $roles = [],
        bool $isTest = true,
    ): User {
        return (new User())
            ->setEmail($email)
            ->setName($name)
            ->setRoles($roles)
            ->setIsTest($isTest);
    }
}
