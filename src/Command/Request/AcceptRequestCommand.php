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

namespace KaLehmann\UnlockedServer\Command\Request;

use KaLehmann\UnlockedServer\Repository\RequestRepository;
use KaLehmann\UnlockedServer\Service\RequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'unlocked:request:accept',
    description: 'Accept a request of the unlocked server.',
    hidden: false,
)]
class AcceptRequestCommand extends Command
{
    public function __construct(
        private RequestRepository $requestRepository,
        private RequestService $requestService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'request-id',
            InputArgument::REQUIRED,
            'The id of the request to accept',
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle($input, $output);
        $requestId = $input->getArgument('request-id');
        if (false === is_numeric($requestId)) {
            $io->error('The id of the request must be numeric!');

            return Command::FAILURE;
        }
        $request = $this->requestRepository->find((int) $requestId);
        if (null === $request) {
            $io->error('The request with id "' . $requestId . '" not found!');

            return Command::FAILURE;
        }
        $this->requestService->acceptRequest($request);

        $io->success('Accepted request "' . $request->getId() . '"');

        return Command::SUCCESS;
    }
}
