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

use KaLehmann\UnlockedServer\Form\Type\AcceptRequestType;
use KaLehmann\UnlockedServer\Form\Type\DenyRequestType;
use KaLehmann\UnlockedServer\Model\Request as RequestModel;
use KaLehmann\UnlockedServer\Model\User;
use KaLehmann\UnlockedServer\Repository\RequestRepository;
use KaLehmann\UnlockedServer\Service\RequestService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestController extends AbstractController
{
    public function accept(
        LoggerInterface $logger,
        Request $request,
        RequestRepository $requestRepository,
        RequestService $requestService,
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(AcceptRequestType::class);
        $form->handleRequest($request);
        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('requests_accept') .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('requests_accept') .
                    '" but form was not submitted',
                );
            }

            return new RedirectResponse(
                $this->generateUrl('requests_list'),
            );
        }
        $formData = $form->getData();
        if (false === is_array($formData)) {
            throw new \RuntimeException(
                'Expected form data to be an array, got ' . gettype($formData),
            );
        }
        ['id' => $id] = $formData;
        $request = $requestRepository->find($id);
        if (null === $request) {
            throw new NotFoundHttpException();
        }
        if ($request->getUser() !== $user) {
            throw new BadRequestHttpException();
        }

        $requestService->acceptRequest($request);

        return $this->redirectToRoute('requests_list');
    }

    public function deny(
        LoggerInterface $logger,
        Request $request,
        RequestRepository $requestRepository,
        RequestService $requestService,
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(DenyRequestType::class);
        $form->handleRequest($request);
        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('requests_deny') .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $this->generateUrl('requests_deny') .
                    '" but form was not submitted',
                );
            }

            return new RedirectResponse(
                $this->generateUrl('requests_list'),
            );
        }
        $formData = $form->getData();
        if (false === is_array($formData)) {
            throw new \RuntimeException(
                'Expected form data to be an array, got ' . gettype($formData),
            );
        }
        ['id' => $id] = $formData;
        $request = $requestRepository->find($id);
        if (null === $request) {
            throw new NotFoundHttpException();
        }
        if ($request->getUser() !== $user) {
            throw new BadRequestHttpException();
        }

        $requestService->denyRequest($request);

        return $this->redirectToRoute('requests_list');
    }

    public function list(
        Request $request,
        RequestRepository $requestRepository,
    ): Response {
        $user = $this->getUser();
        if (false === $user instanceof User) {
            throw new \RuntimeException(
                'Expected Controller::getUser() to return a User model, got ' .
                (is_object($user) ? get_class($user) : gettype($user)),
            );
        }
        $page = intval($request->query->get('page', 1));
        if ($page < 1) {
            return $this->redirectToRoute('requests_list', ['page' => 1]);
        }
        $pageSize = 15;
        $requestsPaginator = $requestRepository->searchPaginated($user, $page, $pageSize);
        $totalRequests = count($requestsPaginator);
        $pageCount = ceil($totalRequests / $pageSize);
        if ($page > $pageCount) {
            return $this->redirectToRoute('requests_list', ['page' => $pageCount]);
        }
        $requests = array_map(
            function (RequestModel $request): array {
                $data = [
                    'request' => $request,
                ];
                if (RequestModel::STATE_PENDING === $request->getState()) {
                    $data['acceptForm'] = $this->createForm(
                        AcceptRequestType::class,
                        $request,
                    )->createView();
                    $data['denyForm'] = $this->createForm(
                        DenyRequestType::class,
                        $request,
                    )->createView();
                }
                return $data;
            },
            iterator_to_array($requestsPaginator),
        );

        return $this->render(
            'requests/list.html.twig',
            [
                'requests' => $requests,
                'pagination' => [
                    'total' => $totalRequests,
                    'page' => $page,
                    'page_count' => $pageCount,
                ],
            ],
        );
    }
}
