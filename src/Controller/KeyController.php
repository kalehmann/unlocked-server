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
use KaLehmann\UnlockedServer\DTO\CreateKeyDto;
use KaLehmann\UnlockedServer\DTO\DeleteKeyDto;
use KaLehmann\UnlockedServer\DTO\EditKeyDto;
use KaLehmann\UnlockedServer\DTO\SearchKeyDto;
use KaLehmann\UnlockedServer\Form\Type\ConfirmKeyDeletionType;
use KaLehmann\UnlockedServer\Form\Type\CreateKeyType;
use KaLehmann\UnlockedServer\Form\Type\EditKeyType;
use KaLehmann\UnlockedServer\Form\Type\SearchKeyType;
use KaLehmann\UnlockedServer\Mapping\KeyMapper;
use KaLehmann\UnlockedServer\Model\Key;
use KaLehmann\UnlockedServer\Model\User;
use KaLehmann\UnlockedServer\Repository\KeyRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\ClickableInterface;

class KeyController extends AbstractController
{
    public function add(): Response
    {
        $createKeyDto = new CreateKeyDto();
        $createForm = $this->createForm(CreateKeyType::class, $createKeyDto);

        return $this->render(
            'keys/create.html.twig',
            [
                'createForm' => $createForm->createView(),
            ],
        );
    }

    public function confirmDeletion(
        KeyMapper $keyMapper,
        KeyRepository $keyRepository,
        string $handle,
    ): Response {
        $key = $keyRepository->find($handle);
        if (null === $key) {
            throw new NotFoundHttpException();
        }
        if ($key->getUser() !== $this->getUser()) {
            throw new BadRequestHttpException();
        }
        $deleteKeyDto = new DeleteKeyDto();
        $keyMapper->mapModelToDeleteDto($key, $deleteKeyDto);
        $form = $this->createForm(ConfirmKeyDeletionType::class, $deleteKeyDto);

        return $this->render(
            'keys/delete.html.twig',
            [
                'confirmForm' => $form->createView(),
                'handle' => $handle,
            ],
        );
    }

    public function create(
        EntityManagerInterface $entityManager,
        KeyMapper $keyMapper,
        LoggerInterface $logger,
        Request $request,
    ): Response {
        $createKeyDto = new CreateKeyDto();
        $createForm = $this->createForm(CreateKeyType::class, $createKeyDto);
        $createForm->handleRequest($request);
        $isSubmitted = $createForm->isSubmitted();
        if (false === $isSubmitted || false === $createForm->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('keys_create') .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('keys_create') .
                    '" but form was not submitted',
                );
            }

            return $this->render(
                'keys/create.html.twig',
                [
                    'createForm' => $createForm->createView(),
                ],
            );
        }
        $user = $this->getUser();
        if (false === $user instanceof User) {
            throw new \RuntimeException(
                'Expected Controller::getUser() to return a User model, got ' .
                (is_object($user) ? get_class($user) : gettype($user)),
            );
        }
        $key = $keyMapper->createModelFromCreateDto($createKeyDto, $user);
        $entityManager->persist($key);
        $entityManager->flush();

        return $this->redirectToRoute('keys_list');
    }

    public function delete(
        KeyRepository $keyRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        Request $request,
        string $handle,
    ): Response {
        $deleteKeyDto = new DeleteKeyDto();
        $form = $this->createForm(ConfirmKeyDeletionType::class, $deleteKeyDto);
        $form->handleRequest($request);
        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('keys_delete', ['handle' => $handle]) .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('keys_delete', ['handle' => $handle]) .
                    '" but form was not submitted',
                );
            }

            return $this->render(
                'keys/delete.html.twig',
                [
                    'confirmForm' => $form->createView(),
                    'handle' => $handle,
                ],
            );
        }
        if ($handle !== $deleteKeyDto->handle) {
            throw new BadRequestHttpException();
        }
        $key = $keyRepository->find($deleteKeyDto->handle);
        if (null === $key) {
            throw new NotFoundHttpException();
        }
        if ($key->getUser() !== $this->getUser()) {
            throw new BadRequestHttpException();
        }
        if ($key->isDeleted()) {
            return $this->redirectToRoute('keys_list');
        }

        $confirmButton = $form->get('confirm');
        if (false === $confirmButton instanceof ClickableInterface) {
            throw new RuntimeException(
                'Expected Button in the ConfirmKeyDeletion form to implement ' .
                'the ClickableInterface, got class "' .
                get_class($confirmButton) . '" instead.',
            );
        }
        if ($confirmButton->isClicked()) {
            $key->delete();
            $entityManager->persist($key);
            $entityManager->flush();
        }

        return $this->redirectToRoute('keys_list');
    }

    public function edit(
        KeyMapper $keyMapper,
        KeyRepository $keyRepository,
        string $handle,
    ): Response {
        $key = $keyRepository->find($handle);
        if (null === $key) {
            throw new NotFoundHttpException();
        }
        if ($key->isDeleted()) {
            return $this->redirectToRoute('keys_list');
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
        LoggerInterface $logger,
        Request $request,
    ): Response {
        $searchKeyDto = new SearchKeyDto();
        $searchForm = $this->createForm(
            SearchKeyType::class,
            $searchKeyDto,
            [
                'allow_extra_fields' => true,
            ],
        );
        $searchForm->handleRequest($request);
        $user = $this->getUser();
        if (false === $user instanceof User) {
            throw new \RuntimeException(
                'Expected Controller::getUser() to return a User model, got ' .
                (is_object($user) ? get_class($user) : gettype($user)),
            );
        }
        $page = intval($request->query->get('page', 1));
        if ($page < 1) {
            return $this->redirectToRoute('keys_list', ['page' => 1]);
        }
        $pageSize = 15;
        $keyPaginator = $keyRepository->searchPaginated(
            $user,
            $page,
            $pageSize,
            $searchKeyDto->query,
            $searchKeyDto->showDeleted,
        );
        $pageCount = max(ceil(count($keyPaginator) / $pageSize), 1);
        if ($page > $pageCount) {
            return $this->redirectToRoute('keys_list', ['page' => $pageCount]);
        }

        return $this->render(
            'keys/list.html.twig',
            [
                'keys' => $keyPaginator,
                'pagination' => [
                    'page' => $page,
                    'page_count' => $pageCount,
                ],
                'query' => $searchKeyDto->query,
                'showDeleted' => $searchKeyDto->showDeleted,
                'searchForm' => $searchForm->createView(),
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
        $editKeyDto = new EditKeyDto();
        $form = $this->createForm(EditKeyType::class, $editKeyDto);
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
        if ($handle !== $editKeyDto->handle) {
            throw new BadRequestHttpException();
        }
        $key = $keyRepository->find($editKeyDto->handle);
        if (null === $key) {
            throw new NotFoundHttpException();
        }
        if ($key->getUser() !== $this->getUser()) {
            throw new BadRequestHttpException();
        }
        if ($key->isDeleted()) {
            return $this->redirectToRoute('keys_list');
        }
        $keyMapper->mapEditDtoToModel($editKeyDto, $key);
        $entityManager->persist($key);
        $entityManager->flush();

        return $this->redirectToRoute('keys_list');
    }
}
