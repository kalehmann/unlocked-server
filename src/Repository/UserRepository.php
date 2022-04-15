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

namespace KaLehmann\UnlockedServer\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KaLehmann\UnlockedServer\Model\User;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return callable(string): array<string>
     */
    public function getUserHandlesAutocompleter(): callable
    {
        return function (string $handle): array {
            if (!$handle) {
                return [];
            }
            $entityManager = $this->getEntityManager();
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder->select('u.handle')
                         ->from(User::class, 'u')
                         ->where(
                             $queryBuilder->expr()->like(
                                 'u.handle',
                                 ':handle',
                             ),
                         )
                         ->setParameter(
                             'handle',
                             $handle . '%',
                         );

            return array_map(
                fn (array $user): string => $user['handle'],
                $queryBuilder
                    ->getQuery()
                    ->getResult(),
            );
        };
    }
}
