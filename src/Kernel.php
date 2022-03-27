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

namespace KaLehmann\UnlockedServer;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @return array<BundleInterface>
     */
    public function registerBundles(): array
    {
        $bundles = [
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
        ];

        return $bundles;
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->import('../config/{packages}/*.yaml');
        $c->import('../config/{packages}/' . $this->environment . '/*.yaml');
        // register all classes in /src/ as service
        $c->services()
            ->load('KaLehmann\\UnlockedServer\\', __DIR__ . '/*')
            ->autowire()
            ->autoconfigure()
        ;

        $c->services()
            ->load('KaLehmann\\UnlockedServer\\Controller\\', __DIR__ . '/Controller/*')
            ->tag('controller.service_arguments')
            ->autowire()
            ->autoconfigure()
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        // load the yaml routes
        $routes->import(__DIR__ . '/../config/routes.yaml', 'yaml');
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/../var/cache/' . $this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../var/log';
    }
}
