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
}
