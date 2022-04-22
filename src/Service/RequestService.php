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

namespace KaLehmann\UnlockedServer\Service;

use Doctrine\ORM\EntityManagerInterface;
use KaLehmann\UnlockedServer\Model\Client;
use KaLehmann\UnlockedServer\Model\Key;
use KaLehmann\UnlockedServer\Model\Request;

class RequestService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function acceptRequest(
        Request $request,
    ): void {
        if (Request::STATE_PENDING !== $request->getState()) {
            throw new \RuntimeException(
                'Could not accept request "' . $request->getId() .
                '" - only a pending request can be accepted',
            );
        }
        $request->setState(Request::STATE_ACCEPTED);
        $request->setProcessed(time());

        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    public function createRequest(
        Client $client,
        Key $key,
    ): Request {
        $now = time();
        $request = new Request(
            $client,
            $key,
            $key->getUser(),
            $now,
            $now + 3600 * 24,
        );
        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }

    public function denyRequest(
        Request $request,
    ): void {
        if (Request::STATE_PENDING !== $request->getState()) {
            throw new \RuntimeException(
                'Could not deny request "' . $request->getId() .
                '" - only a pending request can be denied',
            );
        }
        $request->setState(Request::STATE_DENIED);
        $request->setProcessed(time());

        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    public function fulfillRequest(
        Request $request,
    ): string {
        if (Request::STATE_ACCEPTED !== $request->getState()) {
            throw new \RuntimeException(
                'Could not fulfill request "' . $request->getId() .
                '" as it was not accepted',
            );
        }

        $request->setState(Request::STATE_FULFILLED);
        $request->setFulfilled(time());

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request->getKey()->getKey();
    }
}
