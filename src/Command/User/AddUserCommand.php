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

namespace KaLehmann\UnlockedServer\Command\User;

use Doctrine\ORM\EntityManagerInterface;
use KaLehmann\UnlockedServer\Model\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'unlocked:user:add',
    description: 'Creates a new user for the unlocked server.',
    hidden: false,
)]
class AddUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'handle',
            InputArgument::REQUIRED,
            'The unique handle of the user',
        );
        $this->addOption(
            'password',
            null,
            InputOption::VALUE_REQUIRED,
            'The password of the new user',
        );
        $this->addOption(
            'email',
            null,
            InputOption::VALUE_REQUIRED,
            'The e-mail address of the new user',
        );
        $this->addOption(
            'phone',
            null,
            InputOption::VALUE_REQUIRED,
            'The phone number of the new user',
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $email = $input->getOption('email');
        $handle = $input->getArgument('handle');
        $password = $input->getOption('password');
        $phone = $input->getOption('phone');
        $user = new User($handle);
        $user->setEmail($email);
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $password
            ),
        );
        if ($phone) {
            $user->setMobile($phone);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    protected function interact(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $io = new SymfonyStyle($input, $output);
        if (!$input->getArgument('handle')) {
            $handle = $io->ask(
                'Handle of the new user : ',
                null,
                function (string $handle): string {
                    if (empty($handle)) {
                        throw new \RuntimeException(
                            'Handle can not be empty.',
                        );
                    }

                    return $handle;
                },
            );
            $input->setArgument('handle', $handle);
        }
        if (!$input->getOption('password')) {
            $password = $io->askHidden(
                'Password of the new user : ',
                function (string $password): string {
                    if (empty($password)) {
                        throw new \RuntimeException(
                            'Password cannot be empty.',
                        );
                    }

                    return $password;
                },
            );
            $input->setOption('password', $password);
        }
        if (!$input->getOption('email')) {
            $email = $io->ask(
                'E-mail address of the new user : ',
                null,
                function (string $email): string {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException(
                            'E-mail address is invalid.',
                        );
                    }

                    return $email;
                },
            );
            $input->setOption('email', $email);
        }
    }
}
