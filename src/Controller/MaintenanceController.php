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

namespace KaLehmann\UnlockedServer\Controller;

use KaLehmann\UnlockedServer\Service\ListDatabasesService;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class MaintenanceController
{
    public function status(
        ListDatabasesService $listDatabasesService,
        Environment $twig,
    ): Response {
        $databaseExists = in_array(
            $listDatabasesService->getDefaultDatabaseName(),
            $listDatabasesService->getDatabases(),
        );

        return new Response(
            $twig->render(
                'maintenance/init.html.twig',
                [
                    'dbExists' => $databaseExists,
                ],
            ),
            Response::HTTP_OK
        );
    }
}
