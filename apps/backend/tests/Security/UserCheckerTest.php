<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class UserCheckerTest extends TestCase
{
    public function testAllowsActiveUser(): void
    {
        $user = (new User())->setEmail('a@renlo.local')->setPassword('x');
        $checker = new UserChecker();
        $checker->checkPreAuth($user);
        $checker->checkPostAuth($user);
        self::assertFalse($user->isDeleted());
    }

    public function testRejectsSoftDeletedUser(): void
    {
        $user = (new User())->setEmail('d@renlo.local')->setPassword('x');
        $user->softDelete();
        $checker = new UserChecker();

        $this->expectException(DisabledException::class);
        $checker->checkPreAuth($user);
    }

    public function testIgnoresNonAppUser(): void
    {
        $checker = new UserChecker();
        $checker->checkPreAuth(new InMemoryUser('guest', null));
        self::assertTrue(true);
    }
}
