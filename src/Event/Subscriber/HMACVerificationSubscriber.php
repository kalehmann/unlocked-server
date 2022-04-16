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

namespace KaLehmann\UnlockedServer\Event\Subscriber;

use KaLehmann\UnlockedServer\Model\Client;
use KaLehmann\UnlockedServer\Model\Token;
use KaLehmann\UnlockedServer\Model\User;
use KaLehmann\UnlockedServer\Security\Passport\Badge\HMACBadge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class HMACVerificationSubscriber implements EventSubscriberInterface
{
    public function checkPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $hmacBadge = $passport->getBadge(HMACBadge::class);
        if (null === $hmacBadge) {
            return;
        }
        if (false === $hmacBadge instanceof HMACBadge) {
            throw new \RuntimeException(
                'Expected HMACBadge, got ' . get_class($hmacBadge),
            );
        }

        $userBadge = $passport->getBadge(UserBadge::class);
        if (null === $userBadge) {
            return;
        }
        if (false === $userBadge instanceof UserBadge) {
            throw new \RuntimeException(
                'Expected UserBadge, got ' . get_class($userBadge),
            );
        }

        $algorithm = $hmacBadge->getAlgorithm();
        if (false === in_array($algorithm, hash_hmac_algos(), true)) {
            throw new \RuntimeException(
                'The algorithm "' . $algorithm . '" is not supported for ' .
                'HMAC, supported algorithms are ' .
                implode(', ', hash_hmac_algos()),
            );
        }

        $dataToSign = '';
        foreach ($hmacBadge->getHeaders() as $headerName => $header) {
            $dataToSign .= $headerName . ': ' . $header . PHP_EOL;
        }
        $dataToSign .= $hmacBadge->getMessage();

        $user = $userBadge->getUser();
        $key = $this->getKeyFromUserInterface($user, $hmacBadge->getHandle());
        $expectedSignature = hash_hmac(
            $algorithm,
            $dataToSign,
            $key,
        );

        if ($expectedSignature !== strtolower($hmacBadge->getSignature())) {
            throw new \RuntimeException(
                'The provided HMAC signature for the request could not be ' .
                'reproduced',
            );
        }

        $hmacBadge->resolve();
    }

    /**
     * @return array<string, array<array<string|int>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => [
                ['checkPassport', 512],
            ],
        ];
    }

    private function getKeyFromUserInterface(
        UserInterface $user,
        string $handle,
    ): string {
        if ($user instanceof Client) {
            return $user->getSecret();
        }

        if ($user instanceof User) {
            $token = $user->getTokens()->filter(
                fn (Token $token) => (string) $token->getId() === $handle,
            )->first();
            if (false === $token) {
                throw new \RuntimeException(
                    'Expected user "' . $user->getHandle() . '" to have a ' .
                    'token with the id ' . $handle,
                );
            }
            return $token->getToken();
        }

        throw new \RuntimeException(
            'Expected UserBadge to provider either "' .
            Client::class . '" or "' . User::class .
            '", got "' . get_class($user) . '"',
        );
    }
}
