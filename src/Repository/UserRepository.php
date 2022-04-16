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
use KaLehmann\UnlockedServer\Model\Token;
use KaLehmann\UnlockedServer\Model\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements
    UserLoaderInterface
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

    public function loadUserByIdentifier(string $identifier): ?User
    {
        $qb = $this->createQueryBuilder('u');
        $qb->leftJoin(
            'u.tokens',
            't',
        )->where(
            $qb->expr()->orX(
                $qb->expr()->eq('t.id', ':identifier'),
                $qb->expr()->eq('u.handle', ':identifier'),
            ),
        )->setParameter('identifier', $identifier);

        $user = $qb->getQuery()->getOneOrNullResult();
        if (false === $user instanceof User) {
            return null;
        }

        return $user;
    }
}
