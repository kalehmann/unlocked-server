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
use KaLehmann\UnlockedServer\DTO\EditKeyDto;
use KaLehmann\UnlockedServer\Form\Type\EditKeyType;
use KaLehmann\UnlockedServer\Mapping\KeyMapper;
use KaLehmann\UnlockedServer\Model\Key;
use KaLehmann\UnlockedServer\Repository\KeyRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KeyController extends AbstractController
{
    public function edit(
        KeyMapper $keyMapper,
        KeyRepository $keyRepository,
        string $handle,
    ): Response {
        $key = $keyRepository->find($handle);
        if (null === $key) {
            throw new NotFoundHttpException();
        }
        $user = $this->getUser();
        if ($user !== $key->getUser()) {
            throw new BadRequestHttpException();
        }
        $editKeyDto = new EditKeyDto();
        $keyMapper->mapModelToEditDto($key, $editKeyDto);
        $form = $this
            ->createForm(EditKeyType::class, $editKeyDto)
            ->createView();

        return $this->render(
            'keys/edit.html.twig',
            [
                'editForm' => $form,
            ],
        );
    }

    public function list(
        KeyRepository $keyRepository,
    ): Response {
        $user = $this->getUser();
        $keys = $keyRepository->findBy(
            [
                'user' => $user,
            ],
        );

        return $this->render(
            'keys/list.html.twig',
            [
                'keys' => $keys,
            ],
        );
    }

    public function save(
        KeyMapper $keyMapper,
        KeyRepository $keyRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Request $request,
        string $handle,
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(EditKeyType::class, new EditKeyDto());
        $form->handleRequest($request);
        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('keys_save', ['handle' => $handle]) .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('keys_save', ['handle' => $handle]) .
                    '" but form was not submitted',
                );
            }

            return $this->render(
                'keys/edit.html.twig',
                [
                    'editForm' => $form->createView(),
                ],
            );
        }
        $editKeyDto = $form->getData();
        if (false === is_object($editKeyDto)) {
            throw new \RuntimeException(
                'Expected form data to be an EditKeyDto, got ' .
                gettype($editKeyDto),
            );
        }
        if (false === $editKeyDto instanceof EditKeyDto) {
            throw new \RuntimeException(
                'Expected form data to be a EditKeyDto, got ' .
                get_class($editKeyDto),
            );
        }
        if ($handle !== $editKeyDto->handle) {
            throw new BadRequestHttpException();
        }
        $key = $keyRepository->find($editKeyDto->handle);
        if (null === $key) {
            throw new NotFoundHttpException();
        }
        if ($key->getUser() !== $user) {
            throw new BadRequestHttpException();
        }
        $keyMapper->mapEditDtoToModel($editKeyDto, $key);
        $entityManager->persist($key);
        $entityManager->flush();

        return $this->redirectToRoute('keys_list');
    }
}
