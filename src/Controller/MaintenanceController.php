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

use KaLehmann\UnlockedServer\Service\ListDatabasesService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class MaintenanceController
{
    public function initDb(
        KernelInterface $kernel,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        return $this->commandAction(
            $kernel,
            $twig,
            $urlGenerator,
            'doctrine:database:create',
            [
                '-vvv' => true,
            ],
        );
    }

    public function status(
        FormFactoryInterface $formFactory,
        ListDatabasesService $listDatabasesService,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $databaseExists = in_array(
            $listDatabasesService->getDefaultDatabaseName(),
            $listDatabasesService->getDatabases(),
        );

        $form = $formFactory->createBuilder()
                            ->add(
                                'save',
                                SubmitType::class,
                                [
                                    'label' => 'Create Database',
                                ],
                            )
                            ->setAction(
                                $urlGenerator->generate('maintenance_init_db'),
                            )
                            ->getForm()
                            ->createView();

        return new Response(
            $twig->render(
                'maintenance/status.html.twig',
                [
                    'dbExists' => $databaseExists,
                    'form' => $form,
                ],
            ),
            Response::HTTP_OK
        );
    }

    /**
     * @param KernelInterface $kernel
     * @param Environment $twig
     * @param UrlGeneratorInterface $urlGenerator
     * @param string $command
     * @param array<string, scalar> $parameters
     *
     * @return Response
     */
    private function commandAction(
        KernelInterface $kernel,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        string $command,
        array $parameters,
    ): Response {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => $command,
            ...$parameters,
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);
        $content = $output->fetch();
        $commandString = $command . ' ' .
                       implode(
                           ' ',
                           array_map(
                               fn($value, string $key) => $key . '=' . $value,
                               $parameters,
                               array_keys($parameters),
                           ),
                       );

        return new Response(
            $twig->render(
                'maintenance/command_output.html.twig',
                [
                    'backUrl' => $urlGenerator->generate('maintenance_status'),
                    'command' => $commandString,
                    'output' => $content,
                ],
            ),
            Response::HTTP_OK
        );
    }
}
