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

use Doctrine\ORM\EntityManagerInterface;
use KaLehmann\UnlockedServer\DTO\EditClientDto;
use KaLehmann\UnlockedServer\Form\Type\EditClientType;
use KaLehmann\UnlockedServer\Mapping\ClientMapper;
use KaLehmann\UnlockedServer\Model\Client;
use KaLehmann\UnlockedServer\Repository\ClientRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClientController extends AbstractController
{
    public function edit(
        ClientMapper $clientMapper,
        ClientRepository $clientRepository,
        string $handle,
    ): Response {
        $client = $clientRepository->find($handle);
        if (null === $client) {
            throw new NotFoundHttpException();
        }
        $user = $this->getUser();
        if ($user !== $client->getUser()) {
            throw new BadRequestHttpException();
        }
        $editClientDto = new EditClientDto();
        $clientMapper->mapModelToEditDto($client, $editClientDto);
        $form = $this
            ->createForm(EditClientType::class, $editClientDto)
            ->createView();

        return $this->render(
            'clients/edit.html.twig',
            [
                'editForm' => $form,
            ],
        );
    }

    public function save(
        ClientMapper $clientMapper,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Request $request,
        string $handle,
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(EditClientType::class, new EditClientDto());
        $form->handleRequest($request);
        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('clients_save', ['handle' => $handle]) .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('clients_save', ['handle' => $handle]) .
                    '" but form was not submitted',
                );
            }

            return $this->render(
                'clients/edit.html.twig',
                [
                    'editForm' => $form->createView(),
                ],
            );
        }
        $editClientDto = $form->getData();
        if (false === is_object($editClientDto)) {
            throw new \RuntimeException(
                'Expected form data to be a client, got ' . gettype($editClientDto),
            );
        }
        if (false === $editClientDto instanceof EditClientDto) {
            throw new \RuntimeException(
                'Expected form data to be a client, got ' . get_class($editClientDto),
            );
        }
        if ($handle !== $editClientDto->handle) {
            throw new BadRequestHttpException();
        }
        $client = $clientRepository->find($editClientDto->handle);
        if (null === $client) {
            throw new NotFoundHttpException();
        }
        if ($client->getUser() !== $user) {
            throw new BadRequestHttpException();
        }
        $clientMapper->mapEditDtoToModel($editClientDto, $client);
        $entityManager->persist($client);
        $entityManager->flush();

        return $this->redirectToRoute('clients_list');
    }

    public function list(
        ClientRepository $clientRepository,
    ): Response {
        $user = $this->getUser();
        $clients = $clientRepository->findBy(['user' => $user]);

        return $this->render(
            'clients/list.html.twig',
            [
                'clients' => $clients,
            ],
        );
    }
}
