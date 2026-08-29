<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class UserSeedDefinitions
{
    /**
     * @var list<array{
     *     email: string,
     *     password: string,
     *     roles: list<string>,
     *     lastName: string,
     *     firstName: string,
     *     patronymic: string,
     *     phone: string,
     *     telegram: string,
     *     whatsapp: string,
     *     viber: string,
     *     instagram: string,
     *     photo: string
     * }>
     */
    public const ENTRIES = [
        [
            'email' => 'admin@renlo.local',
            'password' => 'Admin123!',
            'roles' => ['ROLE_ADMIN'],
            'lastName' => 'Иванов',
            'firstName' => 'Андрей',
            'patronymic' => 'Сергеевич',
            'phone' => '+375291450317',
            'telegram' => 'andrey_ivanov_demo',
            'whatsapp' => '+375291450317',
            'viber' => '+375291450317',
            'instagram' => 'andrey.ivanov.demo',
            'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=256&h=256&fit=crop&auto=format',
        ],
        [
            'email' => 'user@renlo.local',
            'password' => 'User123!',
            'roles' => [],
            'lastName' => 'Смирнова',
            'firstName' => 'Ольга',
            'patronymic' => 'Ивановна',
            'phone' => '+375336184205',
            'telegram' => 'olga_smirnova_demo',
            'whatsapp' => '+375336184205',
            'viber' => '+375336184205',
            'instagram' => 'olga.smirnova.demo',
            'photo' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=256&h=256&fit=crop&auto=format',
        ],
        [
            'email' => 'seller@renlo.local',
            'password' => 'User123!',
            'roles' => [],
            'lastName' => 'Петров',
            'firstName' => 'Максим',
            'patronymic' => 'Олегович',
            'phone' => '+375447203916',
            'telegram' => 'maksim_petrov_demo',
            'whatsapp' => '+375447203916',
            'viber' => '+375447203916',
            'instagram' => 'maksim.petrov.demo',
            'photo' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=256&h=256&fit=crop&auto=format',
        ],
        [
            'email' => 'buyer@renlo.local',
            'password' => 'User123!',
            'roles' => [],
            'lastName' => 'Соколова',
            'firstName' => 'Наталья',
            'patronymic' => 'Викторовна',
            'phone' => '+375259017483',
            'telegram' => 'natalya_sokolova_demo',
            'whatsapp' => '+375259017483',
            'viber' => '+375259017483',
            'instagram' => 'natalya.sokolova.demo',
            'photo' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=256&h=256&fit=crop&auto=format',
        ],
        [
            'email' => 'agent@renlo.local',
            'password' => 'User123!',
            'roles' => [],
            'lastName' => 'Морозов',
            'firstName' => 'Павел',
            'patronymic' => 'Дмитриевич',
            'phone' => '+375298634152',
            'telegram' => 'pavel_morozov_demo',
            'whatsapp' => '+375298634152',
            'viber' => '+375298634152',
            'instagram' => 'pavel.morozov.demo',
            'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=256&h=256&fit=crop&auto=format',
        ],
    ];
}
