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

use KaLehmann\UnlockedServer\Repository\UserRepository;

/**
 * @psalm-type ExtensionStatus = array{
 *     name: string,
 *     installed: string|false,
 *     required: bool,
 * }
 */
class MaintenanceService
{
    public const MIN_PHP_VERSION = '8.1.0';

    public const REQUIRED_EXTENSIONS = [
        'tokenizer',
        'xml',
        'xmlwriter',
    ];

    public function __construct(
        private ListDatabasesService $listDatabasesService,
        private UserRepository $userRepository,
    ) {
    }

    public function databaseExists(): bool
    {
        return in_array(
            $this->listDatabasesService->getDefaultDatabaseName(),
            $this->listDatabasesService->getDatabases(),
        );
    }

    /**
     * @return array<ExtensionStatus>
     */
    public function getExtensionStatus(): array
    {
        $extensions = [];
        foreach (get_loaded_extensions() as $name) {
            $extensions[$name] = [
                'name' => $name,
                'installed' => phpversion($name),
                'required' => false,
            ];
        }
        foreach (self::REQUIRED_EXTENSIONS as $name) {
            $ext = $extensions[$name] ?? [
                'name' => $name,
                'installed' => false,
                'required' => false,
            ];
            $ext['required'] = true;
            $extensions[$name] = $ext;
        }

        return $extensions;
    }

    /**
     * @return bool whether the system has at least one user account
     */
    public function hasUser(): bool
    {
        return $userExists = $this->userRepository->count([]) > 0;
    }
}
