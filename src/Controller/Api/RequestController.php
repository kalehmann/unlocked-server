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

namespace KaLehmann\UnlockedServer\Controller\Api;

use KaLehmann\UnlockedServer\Model\Client;
use KaLehmann\UnlockedServer\Model\Request as RequestModel;
use KaLehmann\UnlockedServer\Repository\KeyRepository;
use KaLehmann\UnlockedServer\Repository\RequestRepository;
use KaLehmann\UnlockedServer\Service\RequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestController extends AbstractController
{
    public function create(
        KeyRepository $keyRepository,
        Request $request,
        RequestService $requestService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        if ('json' !== $request->getContentType()) {
            throw new BadRequestHttpException(
                'Content type is not json',
            );
        }

        $body = json_decode((string) $request->getContent(), true);
        if (false === $body || false === is_array($body)) {
            throw new BadRequestHttpException(
                'Could not decode request body',
            );
        }
        [
            'key' => $keyHandle,
        ] = $body;
        $client = $this->getUser();
        if (null === $client) {
            throw new \RuntimeException(
                'Unable to get current user',
            );
        }
        if (false === $client instanceof Client) {
            throw new \RuntimeException(
                'Expected user to be a client, got "' .
                get_class($client) . '"',
            );
        }

        $key = $keyRepository->find($keyHandle);
        if (null === $key) {
            throw new BadRequestHttpException(
                'Key with the handle "' . $keyHandle . '" not found',
            );
        }
        if ($key->getUser() !== $client->getUser()) {
            throw new BadRequestHttpException(
                'Key "' . $key->getHandle() . '" is inaccessible by the ' .
                'client "' . $client->getHandle() . '"',
            );
        }

        $request = $requestService->createRequest($client, $key);

        return new JsonResponse(
            $request->toArray(),
            Response::HTTP_CREATED,
        );
    }

    public function patch(
        Request $request,
        RequestRepository $requestRepository,
        RequestService $requestService,
        int $id,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        if ('json' !== $request->getContentType()) {
            throw new BadRequestHttpException(
                'Content type is not json',
            );
        }

        $body = json_decode((string) $request->getContent(), true);
        if (false === $body || false === is_array($body)) {
            throw new BadRequestHttpException(
                'Could not decode request body',
            );
        }
        [
            'state' => $newState,
        ] = $body;
        $client = $this->getUser();
        if (null === $client) {
            throw new \RuntimeException(
                'Unable to get current user',
            );
        }
        if (false === $client instanceof Client) {
            throw new \RuntimeException(
                'Expected user to be a client, got "' .
                get_class($client) . '"',
            );
        }
        $request = $requestRepository->find($id);
        if (null === $request) {
            throw new NotFoundHttpException(
                'No request with id ' . $id . ' found',
            );
        }
        if ($request->getClient() !== $client) {
            throw new BadRequestHttpException(
                'Request "' . $request->getId() . '" is inaccessible by the ' .
                'client "' . $client->getHandle() . '"',
            );
        }
        if ($newState !== RequestModel::STATE_FULFILLED) {
            throw new BadRequestHttpException('Invalid state change');
        }

        return new Response(
            $requestService->fulfillRequest($request),
        );
    }

    public function show(
        RequestRepository $requestRepository,
        int $id,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        $client = $this->getUser();
        if (null === $client) {
            throw new \RuntimeException(
                'Unable to get current user',
            );
        }
        if (false === $client instanceof Client) {
            throw new \RuntimeException(
                'Expected user to be a client, got "' .
                get_class($client) . '"',
            );
        }
        $request = $requestRepository->find($id);
        if (null === $request) {
            throw new NotFoundHttpException(
                'No request with id ' . $id . ' found',
            );
        }
        if ($request->getClient() !== $client) {
            throw new BadRequestHttpException(
                'Request "' . $request->getId() . '" is inaccessible by the ' .
                'client "' . $client->getHandle() . '"',
            );
        }

        return new JsonResponse(
            $request->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
