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

namespace KaLehmann\UnlockedServer\Command\Key;

use Doctrine\ORM\EntityManagerInterface;
use KaLehmann\UnlockedServer\Model\Key;
use KaLehmann\UnlockedServer\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'unlocked:key:add',
    description: 'Creates a new key for the unlocked server.',
    hidden: false,
)]
class AddKeyCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'handle',
            InputArgument::REQUIRED,
            'The unique handle of the key',
        );
        $this->addOption(
            'key',
            null,
            InputOption::VALUE_REQUIRED,
            'The secret key',
        );
        $this->addOption(
            'description',
            null,
            InputOption::VALUE_REQUIRED,
            'The description  of the new key',
        );
        $this->addOption(
            'user',
            null,
            InputOption::VALUE_REQUIRED,
            'The handle of the user the new key belongs to',
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $description = $input->getOption('description');
        if (false === is_string($description)) {
            throw new \RuntimeException(
                'Expected `description` to be a type string',
            );
        }
        $handle = $input->getArgument('handle');
        if (false === is_string($handle)) {
            throw new \RuntimeException('Expected `handle` to be a type string');
        }
        $key = $input->getOption('key');
        if (false === is_string($key)) {
            throw new \RuntimeException(
                'Expected `key` to be a type string',
            );
        }
        $userHandle = $input->getOption('user');
        if (false === is_string($userHandle)) {
            throw new \RuntimeException(
                'Expected `user` to be a type string',
            );
        }
        $user = $this->userRepository->find($userHandle);
        if (!$user) {
            throw new \RuntimeException(
                'There is no user with the handle "' . $userHandle . '"',
            );
        }
        $key = new Key(
            $handle,
            $key,
            $user,
        );
        if ($description) {
            $key->setDescription($description);
        }
        $errors = $this->validator->validate($key);
        if (count($errors) > 0) {
            $errorStr = '';
            foreach ($errors as $violation) {
                $root = $violation->getRoot();
                if (is_object($root)) {
                    $errorStr .= get_class($root) . '::' .
                        $violation->getPropertyPath() . ' : ';
                }
                $errorStr .= $violation->getMessage() . PHP_EOL;
            }
            $io->error($errorStr);

            return Command::FAILURE;
        }

        $this->entityManager->persist($key);
        $this->entityManager->flush();

        $io->success('Created key.');

        return Command::SUCCESS;
    }

    protected function interact(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $io = new SymfonyStyle($input, $output);
        if (!$input->getArgument('handle')) {
            $handle = $io->ask(
                'Handle of the new key : ',
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
        if (!$input->getOption('key')) {
            $key = $io->askHidden(
                'Secret key : ',
                function (string $key): string {
                    if (empty($key)) {
                        throw new \RuntimeException(
                            'Secret key cannot be empty.',
                        );
                    }

                    return $key;
                },
            );
            $input->setOption('key', $key);
        }
        if (!$input->getOption('description')) {
            $input->setOption(
                'description',
                $io->ask('Description of the new key : '),
            );
        }
        if (!$input->getOption('user')) {
            $helper = $this->getHelper('question');
            if (false === $helper instanceof QuestionHelper) {
                throw new \RuntimeException(
                    'Expected Command::getHelper(\'question\') to return ' .
                    'QuestionHelper',
                );
            }
            $question = new Question('Handle of the user the new key belongs to : ');
            $question->setAutocompleterCallback(
                $this->userRepository->getUserHandlesAutocompleter(),
            );
            $input->setOption(
                'user',
                $helper->ask($input, $output, $question),
            );
        }
    }
}
