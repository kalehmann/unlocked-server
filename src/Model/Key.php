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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Key
{
    private bool $deleted = false;

    private string $description = '';

    private string $handle;

    private string $key;

    /**
     * @var Collection<int, Request>
     */
    private Collection $requests;

    private User $user;

    public function __construct(
        string $handle,
        string $key,
        User $user,
    ) {
        $this->handle = $handle;
        $this->key = $key;
        $this->requests = new ArrayCollection();
        $this->user = $user;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function delete(): void
    {
        $this->deleted = true;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function addRequest(Request $request): void
    {
        if ($this->requests->contains($request)) {
            return;
        }

        $this->requests->add($request);
    }

    /**
     * @return Collection<int, Request>
     */
    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function removeRequest(Request $request): void
    {
        $this->requests->removeElement($request);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
