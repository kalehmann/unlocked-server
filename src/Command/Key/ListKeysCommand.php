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

use KaLehmann\UnlockedServer\Model\Key;
use KaLehmann\UnlockedServer\Repository\KeyRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'unlocked:key:list',
    description: 'Lists keys of the unlocked server.',
    hidden: false,
)]
class ListKeysCommand extends Command
{
    public function __construct(
        private KeyRepository $keyRepository,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $keys = $this->keyRepository->findAll();
        $table = new Table($output);
        $table->setHeaderTitle('Keys');
        $table->setHeaders(['Handle', 'Description', 'User']);
        $table->setRows(
            array_map(
                fn (Key $key): array => [
                    $key->getHandle(),
                    $key->getDescription(),
                    $key->getUser()->getHandle(),
                ],
                $keys,
            ),
        );
        $table->render();

        return Command::SUCCESS;
    }
}
