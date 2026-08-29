<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class DoctrineDataFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $filters = $this->entityManager->getFilters();

        if (!$filters->isEnabled('soft_delete')) {
            $filters->enable('soft_delete');
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $isAdmin = str_starts_with($path, '/admin');

        if ($filters->isEnabled('test_data')) {
            $filters->disable('test_data');
        }

        $filter = $filters->enable('test_data');

        if ($isAdmin) {
            $raw = $request->query->get('isTest', $request->headers->get('X-Admin-Test-Mode', '0'));
            $wantTest = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            $filter->setParameter('is_test', $wantTest ? '1' : '0');
        } else {
            $filter->setParameter('is_test', '0');
        }
    }
}
