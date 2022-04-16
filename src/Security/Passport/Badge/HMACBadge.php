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

namespace KaLehmann\UnlockedServer\Security\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class HMACBadge implements BadgeInterface
{
    private bool $resolved = false;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private string $algorithm,
        private string $handle,
        private array $headers,
        private string $message,
        private string $signature,
    ) {
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function resolve(): void
    {
        $this->resolved = true;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }
}
