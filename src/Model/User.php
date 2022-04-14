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
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements PasswordAuthenticatedUserInterface, UserInterface
{
    /**
     * @var Collection<int, Client>
     */
    private Collection $clients;

    private string $email;

    private string $handle;

    /**
     * @var Collection<int, Key>
     */
    private Collection $keys;

    private ?string $mobile;

    private string $password;

    /**
     * @var Collection<int, Request>
     */
    private Collection $requests;

    /**
     * @var Collection<int, Token>
     */
    private Collection $tokens;

    public function __construct(string $handle)
    {
        $this->clients = new ArrayCollection();
        $this->handle = $handle;
        $this->keys = new ArrayCollection();
        $this->requests = new ArrayCollection();
        $this->tokens = new ArrayCollection();
    }

    public function addClient(Client $client): void
    {
        if ($this->clients->contains($client)) {
            return;
        }

        $this->clients->add($client);
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function removeClient(Client $client): void
    {
        $this->clients->removeElement($client);
    }

    public function eraseCredentials(): void
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function addKey(Key $key): void
    {
        if ($this->keys->contains($key)) {
            return;
        }

        $this->keys->add($key);
    }

    /**
     * @return Collection<int, Key>
     */
    public function getKeys(): Collection
    {
        return $this->keys;
    }

    public function removeKey(Key $key): void
    {
        $this->keys->removeElement($key);
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
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

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function addToken(Token $token): void
    {
        if ($this->tokens->contains($token)) {
            return;
        }

        $this->tokens->add($token);
    }

    /**
     * @return Collection<int, Token>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function removeToken(Token $token): void
    {
        $this->tokens->removeElement($token);
    }

    public function getUserIdentifier(): string
    {
        return $this->handle;
    }
}
