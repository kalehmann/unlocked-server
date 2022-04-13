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

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Service to query the status of database migrations.
 */
#[Autoconfigure(
    bind: [
        '$doctrineDependencyFactory' => '@doctrine.migrations.dependency_factory',
    ],
)]
class MigrationStatusService
{
    private MigrationStatusCalculator $migrationStatusCalculator;

    public function __construct(
        DependencyFactory $doctrineDependencyFactory,
    ) {
        $this->migrationStatusCalculator = $doctrineDependencyFactory
            ->getMigrationStatusCalculator();
    }

    /**
     * @return bool whether there are migrations in the database, that can not
     *              be found.
     */
    public function unregisteredMigrationsDetected(): bool
    {
        $unregisteredMigrationsCount = count(
            $this
                ->migrationStatusCalculator
                ->getExecutedUnavailableMigrations(),
        );

        return 0 !== $unregisteredMigrationsCount;
    }

    /**
     * @return bool whether all registered (and only the registered) migrations
     *              have been executed.
     */
    public function schemaUpToDate(): bool
    {
        $newMigrationsCount = count(
            $this->migrationStatusCalculator->getNewMigrations(),
        );
        $unregisteredMigrationsCount = count(
            $this
                ->migrationStatusCalculator
                ->getExecutedUnavailableMigrations(),
        );

        return 0 === $newMigrationsCount && 0 === $unregisteredMigrationsCount;
    }
}
