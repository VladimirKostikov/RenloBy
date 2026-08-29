<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Auth\RegisterRequest;
use App\Dto\Auth\UserResponse;
use App\Entity\User;
use App\Http\ApiErrorCode;
use App\Exception\ConflictException;
use App\Exception\ResourceNotFoundException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly TelegramNotificationService $telegramNotificationService,
    ) {
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (!$user instanceof User) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_USER);
        }

        return $user;
    }

    public function buildUserResponse(User $user): UserResponse
    {
        return UserResponse::fromEntity($user);
    }

    public function register(RegisterRequest $request): User
    {
        $email = strtolower(trim($request->email));

        if ($this->userRepository->findOneByEmail($email) instanceof User) {
            throw new ConflictException(ApiErrorCode::AUTH_EMAIL_EXISTS);
        }

        $user = (new User())
            ->setEmail($email)
            ->setPassword($this->passwordHasher->hashPassword(new User(), $request->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->telegramNotificationService->notifyRegistration($user);

        return $user;
    }
}
