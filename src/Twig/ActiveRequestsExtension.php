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

namespace KaLehmann\UnlockedServer\Twig;

use KaLehmann\UnlockedServer\Model\Request;
use KaLehmann\UnlockedServer\Model\User;
use KaLehmann\UnlockedServer\Repository\RequestRepository;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ActiveRequestsExtension extends AbstractExtension
{
    public function __construct(
        private RequestRepository $requestRepository,
        private Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('activeRequests', [$this, 'getActiveRequests']),
        ];
    }

    public function getActiveRequests(): int
    {
        $user = $this->security->getUser();
        if (null === $user) {
            return 0;
        }
        if (false === $user instanceof User) {
            return 0;
        }

        $requests = $this->requestRepository->findBy(
            [
                'state' => Request::STATE_PENDING,
                'user' => $user,
            ],
        );

        return count($requests);
    }
}
