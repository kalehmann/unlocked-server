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
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use KaLehmann\UnlockedServer\Model\Client;
use KaLehmann\UnlockedServer\Model\User;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * @return Paginator<Client>
     */
    public function searchPaginated(
        User $user,
        int $page = 1,
        int $perPage = 15,
        ?string $query = null,
        bool $showDeleted = false,
    ): Paginator {
        $offset = $perPage * ($page - 1);
        $qb = $this->createQueryBuilder('c');
        $qb
            ->where($qb->expr()->eq('c.user', ':user'))
            ->setParameter(':user', $user);
        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.description', ':query'),
                    $qb->expr()->like('c.handle', ':query'),
                )
            )->setParameter('query', '%' . $query . '%');
        }
        if (false === $showDeleted) {
            $qb
                ->andWhere($qb->expr()->eq('c.deleted', ':deleted'))
                ->setParameter('deleted', false);
        }
        $qb
            ->orderBy('c.handle', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        return new Paginator($qb);
    }
}
