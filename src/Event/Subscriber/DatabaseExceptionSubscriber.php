<?php

/**
 *  Copyright (C) 2022 Karsten Lehmann <mail@kalehmann.de>
 *
 *  This file is part of unlocked-server.
 *
 *  unlocked-server is free software: you can redistribute it and/or
 *  modify it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, version 3 of the License.
 *
 *  unlocked-server is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with unlocked-server. If not, see
 *  <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace KaLehmann\UnlockedServer\Event\Subscriber;

use Doctrine\DBAL\Exception\ConnectionException;
use KaLehmann\UnlockedServer\Service\ListDatabasesService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DatabaseExceptionSubscriber implements EventSubscriberInterface
{
    private ListDatabasesService $listDatabasesService;

    private LoggerInterface $logger;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        ListDatabasesService $listDatabasesService,
        LoggerInterface $logger,
        UrlGeneratorInterface $urlGenerator,
    ) {
        $this->listDatabasesService = $listDatabasesService;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return array<string, array<array<string|int>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onException', 1000],
            ],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (($throwable instanceof ConnectionException) === false) {
            return;
        }

        $defaultDb = $this->listDatabasesService->getDefaultDatabaseName();
        $databases = $this->listDatabasesService->getDatabases();
        if (in_array($defaultDb, $databases)) {
            // Error seems not related to a missing database
            return;
        }

        $statusUrl = $this->urlGenerator->generate('maintenance_status');
        $event->setResponse(
            new RedirectResponse($statusUrl),
        );

        $this->logger->warning(
            'Got ConnectionException with "' . $throwable->getMessage() .
            '". Redirecting to maintenance page',
        );
    }
}
