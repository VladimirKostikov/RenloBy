<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Auth\UserResponse;
use App\Dto\User\CreateUserRequest;
use App\Dto\User\UpdateUserRequest;
use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Http\ApiErrorCode;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaUploadService $mediaUploadService,
    ) {
    }

    public function list(): array
    {
        return array_map(
            fn (User $user) => UserResponse::fromEntity($user),
            $this->userRepository->findBy([], ['id' => 'ASC'])
        );
    }

    public function get(int $id): UserResponse
    {
        return UserResponse::fromEntity($this->findEntity($id));
    }

    public function create(CreateUserRequest $request): UserResponse
    {
        $user = (new User())
            ->setEmail($request->email)
            ->setRoles($request->roles)
            ->setPassword($this->passwordHasher->hashPassword(
                new User(),
                $request->password
            ))
            ->setNameParts($request->lastName, $request->firstName, $request->patronymic)
            ->setPhoto($this->mediaUploadService->normalizeAvatarUrl($request->photo))
            ->setPhone($this->normalizeOptional($request->phone))
            ->setInstagram($this->normalizeOptional($request->instagram))
            ->setTelegram($this->normalizeOptional($request->telegram))
            ->setWhatsapp($this->normalizeOptional($request->whatsapp))
            ->setViber($this->normalizeOptional($request->viber));

        if ($request->isTest !== null) {
            $user->setIsTest($request->isTest);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return UserResponse::fromEntity($user);
    }

    public function update(int $id, UpdateUserRequest $request): UserResponse
    {
        $user = $this->findEntity($id);

        if ($request->email !== null) {
            $user->setEmail($request->email);
        }
        if ($request->updateNameParts) {
            $user->setNameParts(
                $request->lastName ?? $user->getLastName(),
                $request->firstName ?? $user->getFirstName(),
                $request->patronymic ?? $user->getPatronymic(),
            );
        }
        if ($request->roles !== null) {
            $user->setRoles($request->roles);
        }
        if ($request->password !== null) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $request->password));
        }
        if ($request->isTest !== null) {
            $user->setIsTest($request->isTest);
        }
        if ($request->clearPhoto) {
            $user->setPhoto(null);
        } elseif ($request->photo !== null) {
            $user->setPhoto($this->mediaUploadService->normalizeAvatarUrl($request->photo));
        }
        if ($request->phone !== null) {
            $user->setPhone($this->normalizeOptional($request->phone));
        }
        if ($request->instagram !== null) {
            $user->setInstagram($this->normalizeOptional($request->instagram));
        }
        if ($request->telegram !== null) {
            $user->setTelegram($this->normalizeOptional($request->telegram));
        }
        if ($request->whatsapp !== null) {
            $user->setWhatsapp($this->normalizeOptional($request->whatsapp));
        }
        if ($request->viber !== null) {
            $user->setViber($this->normalizeOptional($request->viber));
        }

        $this->entityManager->flush();

        return UserResponse::fromEntity($user);
    }

    public function uploadPhoto(int $id, \Symfony\Component\HttpFoundation\File\UploadedFile $file): UserResponse
    {
        $user = $this->findEntity($id);
        $uploaded = $this->mediaUploadService->uploadAvatar($file, $user);
        $user->setPhoto($uploaded->url);
        $this->entityManager->flush();

        return UserResponse::fromEntity($user);
    }

    private function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function delete(int $id): void
    {
        $user = $this->findEntity($id);
        $user->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (!$user instanceof User) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_USER);
        }

        return $user;
    }

    public function exportEmailsCsv(): Response
    {
        $users = $this->userRepository->findBy([], ['id' => 'ASC']);
        $lines = ['email,name,id'];
        foreach ($users as $user) {
            $lines[] = sprintf(
                '%s,%s,%d',
                $this->csvEscape($user->getEmail()),
                $this->csvEscape($user->getName()),
                $user->getId() ?? 0,
            );
        }

        $content = implode("\n", $lines) . "\n";
        $response = new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="renlo-users-emails.csv"',
        ]);

        return $response;
    }

    private function csvEscape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
