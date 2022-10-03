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
use KaLehmann\UnlockedServer\DTO\DeleteClientDto;
use KaLehmann\UnlockedServer\DTO\EditClientDto;
use KaLehmann\UnlockedServer\Form\Type\ConfirmClientDeletionType;
use KaLehmann\UnlockedServer\Form\Type\DeleteClientType;
use KaLehmann\UnlockedServer\Form\Type\EditClientType;
use KaLehmann\UnlockedServer\Mapping\ClientMapper;
use KaLehmann\UnlockedServer\Model\Client;
use KaLehmann\UnlockedServer\Repository\ClientRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\ClickableInterface;

class ClientController extends AbstractController
{
    public function confirmDeletion(
        ClientMapper $clientMapper,
        ClientRepository $clientRepository,
        string $handle,
    ): Response {
        $client = $clientRepository->find($handle);
        if (null === $client) {
            throw new NotFoundHttpException();
        }
        if ($client->getUser() !== $this->getUser()) {
            throw new BadRequestHttpException();
        }
        $deleteClientDto = new DeleteClientDto();
        $clientMapper->mapModelToDeleteDto($client, $deleteClientDto);
        $form = $this->createForm(ConfirmClientDeletionType::class, $deleteClientDto);

        return $this->render(
            'keys/delete.html.twig',
            [
                'confirmForm' => $form->createView(),
                'handle' => $handle,
            ],
        );
    }

    public function delete(
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Request $request,
        string $handle,
    ): Response {
        $deleteClientDto = new DeleteClientDto();
        $form = $this->createForm(ConfirmClientDeletionType::class, $deleteClientDto);
        $form->handleRequest($request);
        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('clients_delete', ['handle' => $handle]) .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('clients_delete', ['handle' => $handle]) .
                    '" but form was not submitted',
                );
            }

            return $this->render(
                'clients/delete.html.twig',
                [
                    'confirmForm' => $form->createView(),
                    'handle' => $handle,
                ],
            );
        }
        if ($handle !== $deleteClientDto->handle) {
            throw new BadRequestHttpException();
        }
        $client = $clientRepository->find($deleteClientDto->handle);
        if (null === $client) {
            throw new NotFoundHttpException();
        }
        if ($client->getUser() !== $this->getUser()) {
            throw new BadRequestHttpException();
        }
        if ($client->isDeleted()) {
            return $this->redirectToRoute('clients_list');
        }

        $confirmButton = $form->get('confirm');
        if (false === $confirmButton instanceof ClickableInterface) {
            throw new RuntimeException(
                'Expected Button in the ConfirmClientDeletion form to implement ' .
                'the ClickableInterface, got class "' .
                get_class($confirmButton) . '" instead.',
            );
        }
        if ($confirmButton->isClicked()) {
            $client->delete();
            $entityManager->persist($client);
            $entityManager->flush();
        }

        return $this->redirectToRoute('clients_list');
    }

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
                'Expected form data to be an EditClientDto, got ' .
                gettype($editClientDto),
            );
        }
        if (false === $editClientDto instanceof EditClientDto) {
            throw new \RuntimeException(
                'Expected form data to be an EditClientDto, got ' .
                get_class($editClientDto),
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
}
