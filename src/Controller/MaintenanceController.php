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

use KaLehmann\UnlockedServer\DTO\CreateUserDto;
use KaLehmann\UnlockedServer\Form\Type\BuildDatabaseType;
use KaLehmann\UnlockedServer\Form\Type\CreateUserType;
use KaLehmann\UnlockedServer\Form\Type\RunMigrationsType;
use KaLehmann\UnlockedServer\Repository\UserRepository;
use KaLehmann\UnlockedServer\Service\MaintenanceService;
use KaLehmann\UnlockedServer\Service\MigrationStatusService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @psalm-type CommandParameters = array{
 *     value: string,
 *     secret?: bool,
 * }
 */
class MaintenanceController
{
    public function addUser(
        FormFactoryInterface $formFactory,
        Environment $twig,
    ): Response {
        $dto = new CreateUserDto();
        $form = $formFactory->create(CreateUserType::class, $dto);

        return new Response(
            $twig->render(
                'maintenance/user.html.twig',
                [
                    'createForm' => $form->createView(),
                ],
            ),
            Response::HTTP_OK
        );
    }

    public function createUser(
        FormFactoryInterface $formFactory,
        KernelInterface $kernel,
        LoggerInterface $logger,
        Request $request,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $dto = new CreateUserDto();
        $form = $formFactory->create(CreateUserType::class, $dto);
        $form->handleRequest($request);

        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $urlGenerator->generate('maintenance_create_user') .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $urlGenerator->generate('maintenance_create_user') .
                    '" but form was not submitted',
                );
            }

            return new RedirectResponse(
                $urlGenerator->generate('maintenance_status'),
            );
        }
        $params = [
            'handle' => $dto->handle,
            '--email' => $dto->email,
            '--password' => [
                'value' => $dto->password,
                'secret' => true,
            ],
        ];
        if ($dto->mobile) {
            $params['--phone'] = $dto->mobile;
        }

        return $this->commandAction(
            $kernel,
            $twig,
            $urlGenerator,
            'unlocked:user:add',
            $params,
        );
    }

    public function initDb(
        FormFactoryInterface $formFactory,
        KernelInterface $kernel,
        LoggerInterface $logger,
        Request $request,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $form = $formFactory->create(BuildDatabaseType::class);
        $form->handleRequest($request);

        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $urlGenerator->generate('maintenance_init_db') .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $urlGenerator->generate('maintenance_init_db') .
                    '" but form was not submitted',
                );
            }

            return new RedirectResponse(
                $urlGenerator->generate('maintenance_status'),
            );
        }

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

    public function migrate(
        FormFactoryInterface $formFactory,
        KernelInterface $kernel,
        LoggerInterface $logger,
        Request $request,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $form = $formFactory->create(RunMigrationsType::class);
        $form->handleRequest($request);

        $isSubmitted = $form->isSubmitted();
        if (false === $isSubmitted || false === $form->isValid()) {
            if ($isSubmitted) {
                $logger->warning(
                    'Request to "' .
                    $urlGenerator->generate('maintenance_migrate') .
                    '" with invalid form',
                );
            } else {
                $logger->warning(
                    'Request to "' .
                    $urlGenerator->generate('maintenance_migrate') .
                    '" but form was not submitted',
                );
            }

            return new RedirectResponse(
                $urlGenerator->generate('maintenance_status'),
            );
        }

        return $this->commandAction(
            $kernel,
            $twig,
            $urlGenerator,
            'doctrine:migrations:migrate',
            [
                '--no-interaction' => true,
                '-vvv' => true,
            ],
        );
    }

    public function status(
        FormFactoryInterface $formFactory,
        MaintenanceService $maintenanceService,
        MigrationStatusService $migrationStatusService,
        Environment $twig,
    ): Response {
        $databaseExists = $maintenanceService->databaseExists();
        $unregisteredMigrations = false;
        $schemaUpToDate = false;
        if ($databaseExists) {
            $unregisteredMigrations = $migrationStatusService
                ->unregisteredMigrationsDetected();
            $schemaUpToDate = $migrationStatusService
                ->schemaUpToDate();
        }
        $userExists = false;
        if ($schemaUpToDate) {
            $userExists = $maintenanceService->hasUser();
        }

        $createDbForm = $formFactory
              ->create(BuildDatabaseType::class)
              ->createView();
        $migrationsForm = $formFactory
              ->create(RunMigrationsType::class)
              ->createView();

        $phpVersion = phpversion();
        $phpVersionSufficient = version_compare($phpVersion, '8.1.0', '>=');

        return new Response(
            $twig->render(
                'maintenance/status.html.twig',
                [
                    'dbExists' => $databaseExists,
                    'extensions' => $maintenanceService->getExtensionStatus(),
                    'phpVersion' => $phpVersion,
                    'phpVersionSufficient' => $phpVersionSufficient,
                    'schemaUpToDate' => $schemaUpToDate,
                    'unregisteredMigrations' => $unregisteredMigrations,
                    'userExists' => $userExists,
                    'createDbForm' => $createDbForm,
                    'migrationsForm' => $migrationsForm,
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
     * @param array<string, CommandParameters|scalar> $parameters
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
            ...array_map(
                fn ($value) => is_array($value) ? $value['value'] : $value,
                $parameters
            ),
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);
        $content = $output->fetch();

        return new Response(
            $twig->render(
                'maintenance/command_output.html.twig',
                [
                    'backUrl' => $urlGenerator->generate('maintenance_status'),
                    'command' => $this->getCommandString($command, $parameters),
                    'output' => $content,
                ],
            ),
            Response::HTTP_OK
        );
    }

    /**
     * @param array<string, CommandParameters|scalar> $parameters
     */
    private function getCommandString(
        string $command,
        array $parameters,
    ): string {
        /**
         * @param CommandParameters|string $value
         * @return scalar
         */
        $getValue = static function ($value) {
            if (is_array($value)) {
                if ($value['secret'] ?? false) {
                    return '***';
                }

                return $value['value'];
            }

            return $value;
        };
        $parameters = implode(
            ' ',
            array_map(
                fn($value, string $key) => $key . '=' . $getValue($value),
                $parameters,
                array_keys($parameters),
            ),
        );

        return $command . ' ' . $parameters;
    }
}
