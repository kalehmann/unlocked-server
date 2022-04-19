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

namespace KaLehmann\UnlockedServer\Model;

class Request
{
    public const STATE_ACCEPTED = 'ACCEPTED';

    public const STATE_DENIED = 'DENIED';

    public const STATE_EXPIRED = 'EXPIRED';

    public const STATE_FULFILLED = 'FULFILLED';

    public const STATE_PENDING = 'PENDING';

    private Client $client;

    private int $created;

    private int $expires;

    private ?int $fulfilled = null;

    private int $id;

    private Key $key;

    private ?int $processed = null;

    private string $state = self::STATE_PENDING;

    private User $user;

    public function __construct(
        Client $client,
        Key $key,
        User $user,
        int $created,
        int $expires,
    ) {
        $this->client = $client;
        $this->created = $created;
        $this->expires = $expires;
        $this->key = $key;
        $this->user = $user;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function getFulfilled(): ?int
    {
        return $this->fulfilled;
    }

    public function setFulfilled(int $fulfilled): void
    {
        $this->fulfilled = $fulfilled;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getKey(): Key
    {
        return $this->key;
    }

    public function getProcessed(): ?int
    {
        return $this->processed;
    }

    public function setProcessed(int $processed): void
    {
        $this->processed = $processed;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return array<string, null|scalar>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'client' => $this->getClient()->getHandle(),
            'key' => $this->getKey()->getHandle(),
            'state' => $this->getState(),
            'created' => $this->getCreated(),
            'processed' => $this->getProcessed(),
            'fulfilled' => $this->getFulfilled(),
        ];
    }
}
