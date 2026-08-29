<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserPresenceService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserPresenceServiceTest extends TestCase
{
    public function testTouchSkipsRecentUpdate(): void
    {
        $user = (new User())->setLastSeenAt(new \DateTimeImmutable('-1 minute'));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new UserPresenceService($em);
        $service->touch($user);
    }

    public function testTouchForceUpdates(): void
    {
        $user = (new User())->setLastSeenAt(new \DateTimeImmutable('-1 minute'));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new UserPresenceService($em);
        $service->touch($user, true);

        self::assertInstanceOf(\DateTimeImmutable::class, $user->getLastSeenAt());
    }
}
