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

namespace KaLehmann\UnlockedServer\Mapping;

use KaLehmann\UnlockedServer\DTO\DeleteClientDto;
use KaLehmann\UnlockedServer\DTO\EditClientDto;
use KaLehmann\UnlockedServer\Model\Client;

class ClientMapper
{
    public function mapEditDtoToModel(
        EditClientDto $dto,
        Client $client,
    ): void {
        $client->setDescription($dto->description);
        if ($dto->secret) {
            $client->setSecret($dto->secret);
        }
    }

    public function mapModelToDeleteDto(
        Client $client,
        DeleteClientDto $dto,
    ): void {
        $dto->handle = $client->getHandle();
    }

    public function mapModelToEditDto(
        Client $client,
        EditClientDto $dto,
    ): void {
        $dto->handle = $client->getHandle();
        $dto->description = $client->getDescription();
    }
}
