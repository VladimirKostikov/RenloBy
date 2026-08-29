<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\HeadSnippet\CreateHeadSnippetRequest;
use App\Entity\HeadSnippet;
use App\Repository\HeadSnippetRepository;
use App\Service\HeadSnippetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class HeadSnippetServiceTest extends TestCase
{
    public function testListPublicEnabledIncludesTestFlaggedSnippets(): void
    {
        $enabledTest = (new HeadSnippet())
            ->setName('Analytics')
            ->setCode('<meta name="analytics" content="1">')
            ->setIsEnabled(true)
            ->setIsTest(true);
        $disabled = (new HeadSnippet())
            ->setName('Off')
            ->setCode('<meta name="off" content="0">')
            ->setIsEnabled(false)
            ->setIsTest(false);

        $repository = $this->createMock(HeadSnippetRepository::class);
        $repository->method('findEnabledOrdered')->willReturn([$enabledTest]);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new HeadSnippetService($repository, $em);

        $public = $service->listPublicEnabled();

        self::assertCount(1, $public);
        self::assertSame('<meta name="analytics" content="1">', $public[0]->code);
        self::assertNotSame($disabled->getCode(), $public[0]->code);
    }

    public function testCreateDefaultsIsTestToFalse(): void
    {
        $repository = $this->createMock(HeadSnippetRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $persisted = null;
        $em->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (object $entity) use (&$persisted): bool {
                $persisted = $entity;

                return $entity instanceof HeadSnippet;
            }));
        $em->expects(self::once())->method('flush');

        $service = new HeadSnippetService($repository, $em);
        $service->create(new CreateHeadSnippetRequest(
            'Verify',
            '<meta name="verify" content="ok">',
            true,
            0,
            null,
        ));

        self::assertInstanceOf(HeadSnippet::class, $persisted);
        self::assertFalse($persisted->isTest());
    }
}
