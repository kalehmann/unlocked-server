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

use KaLehmann\UnlockedServer\Model\Request;
use KaLehmann\UnlockedServer\Repository\RequestRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'unlocked:request:list',
    description: 'Lists requests of the unlocked server.',
    hidden: false,
)]
class ListRequestsCommand extends Command
{
    public function __construct(
        private RequestRepository $requestRepository,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $requests = array_reverse(
            $this->requestRepository->findAll(),
        );
        $table = new Table($output);
        $table->setHeaderTitle('Requests');
        $table->setHeaders(
            [
                'Id',
                'Client',
                'Key',
                'State',
                'Created',
                'Processed',
                'Fulfilled',
            ],
        );
        $table->setRows(
            array_map(
                fn (Request $request): array => [
                    $request->getId(),
                    $request->getClient()->getHandle(),
                    $request->getKey()->getHandle(),
                    $request->getState(),
                    date(DATE_ATOM, $request->getCreated()),
                    $request->getProcessed()
                        ? date(DATE_ATOM, $request->getProcessed())
                        : '-',
                    $request->getFulfilled()
                        ? date(DATE_ATOM, $request->getFulfilled())
                        : '-',
                ],
                $requests,
            ),
        );
        $table->render();

        return Command::SUCCESS;
    }
}
