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

namespace KaLehmann\UnlockedServer\Security;

use KaLehmann\UnlockedServer\Security\Passport\Badge\HMACBadge;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class HMACAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (null === $authorizationHeader) {
            throw new CustomUserMessageAuthenticationException(
                'Missing Authorization header',
            );
        }

        $headerIsValid = preg_match(
            '/^hmac username="(?<username>[a-zA-Z0-9\-_]+)", ' .
            'algorithm="(?<algorithm>[a-z0-9-,]+)", ' .
            'headers="(?<headers>[a-z\-\s]+)", ' .
            'signature="(?<signature>[a-zA-Z0-9\+\/=]+)"$/',
            $authorizationHeader,
            $matches,
        );
        if (false === $headerIsValid || 0 === $headerIsValid) {
            throw new CustomUserMessageAuthenticationException(
                'Malformed Authorization header',
            );
        }
        $matches['headers'] = explode(' ', $matches['headers']);
        [
            'algorithm' => $algorithm,
            'signature' => $signature,
            'username' => $username,
        ] = $matches;
        $headers = [];
        foreach ($matches['headers'] as $headerName) {
            $header = $request->headers->get($headerName);
            if (null === $header) {
                throw new CustomUserMessageAuthenticationException(
                    'Expected header "' . $headerName . '" to be provided',
                );
            }
            $headers[$headerName] = $header;
        }
        $message = $request->getContent();
        if (is_resource($message)) {
            throw new \RuntimeException('Expected request body to be string');
        }

        return new SelfValidatingPassport(
            new UserBadge($username),
            [
                new HMACBadge(
                    $algorithm,
                    $username,
                    $headers,
                    $message,
                    $signature,
                ),
            ],
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName,
    ): ?Response {
        return null;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): ?Response {
        $data = [
            'message' => strtr(
                $exception->getMessageKey(),
                $exception->getMessageData(),
            ),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
