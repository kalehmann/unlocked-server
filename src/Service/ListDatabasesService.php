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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Service to list all databases from doctrine's default connection.
 */
class ListDatabasesService
{
    private ManagerRegistry $doctrine;

    public function __construct(
        ManagerRegistry $managerRegistry,
    ) {
        $this->doctrine = $managerRegistry;
    }

    /**
     * Get the name of the database configured at the default connection.
     *
     * @return null|string the default database name or null if the database name
     *                     was not configured.
     *                     For sqlite the path to the database is returned.
     */
    public function getDefaultDatabaseName(): ?string
    {
        $connection = $this->getDefaultConnection();
        $params = $connection->getParams();

        return $params['dbname'] ?? ($params['path'] ?? null);
    }

    /**
     * Gets the names of all databases of the default connection.
     *
     * @return array<string>
     */
    public function getDatabases(): array
    {
        $connection = $this->getDefaultConnection();
        $params = $connection->getParams();
        $driver = $params['driver'] ?? null;
        if ($driver === 'pdo_sqlite') {
            // Sqlite platform does not support ::listDatabases()
            // Either the database does exist or not.
            $path = $params['path'] ?? null;
            if (!$path) {
                return [];
            }
            if (file_exists($path)) {
                return [$path];
            }

            return [];
        }

        unset($params['dbname'], $params['path'], $params['url']);
        $tmpConnection = DriverManager::getConnection($params);
        $tmpConnection->connect();

        return $tmpConnection->getSchemaManager()->listDatabases();
    }

    /**
     * @return Connection the default connection
     */
    private function getDefaultConnection(): Connection
    {
        return $this->doctrine->getConnection(
            $this->doctrine->getDefaultConnectionName(),
        );
    }
}
