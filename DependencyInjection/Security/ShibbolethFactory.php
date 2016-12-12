<?php
/**
 * This file is part of kuleuven/shibboleth-bundle
 *
 * kuleuven/shibboleth-bundle is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * kuleuven/shibboleth-bundle is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with kuleuven/shibboleth-bundle; if not, see
 * <http://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2013 Ronny Moreas, KU Leuven
 *
 * @package     kuleuven/shibboleth-bundle
 * @copyright   (C) 2013 Ronny Moreas, KU Leuven
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL-3
 */
namespace KULeuven\ShibbolethBundle\DependencyInjection\Security;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class ShibbolethFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPoint)
    {
        // Auth provider
        $providerId = $this->createAuthProvider($container, $id, $config, $userProviderId);

        // entry point
        $entryPointId = $this->createEntryPoint($container, $id, $config, $defaultEntryPoint);

        // Listener
        $listenerId = $this->createListener($container, $id, $config, $userProviderId, $entryPointId);

        return array($providerId, $listenerId, $entryPointId);
    }

    public function getKey()
    {
        return 'shibboleth';
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('provider')->end()
                ->booleanNode('use_shibboleth_entry_point')->defaultValue(true)->end()
                ->end()
        ;
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        if (null !== $defaultEntryPoint) {
            return $defaultEntryPoint;
        }
        if ($config['use_shibboleth_entry_point']) {
            $entryPointId = 'security.authentication.entry_point.shibboleth.'.$id;
            $container
                ->setDefinition($entryPointId, new DefinitionDecorator('security.authentication.entry_point.shibboleth'))
            ;
        } else {
            $entryPointId = null;
        }
        return $entryPointId;
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'security.authentication.provider.shibboleth.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('security.authentication.provider.shibboleth'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(2, $id)
        ;
        return $providerId;
    }

    protected function createListener($container, $id, $config, $userProvider, $entryPoint)
    {
        $listenerId = 'security.authentication.listener.shibboleth';
        $listener = new DefinitionDecorator($listenerId);
        $listener->replaceArgument(3, $id);
        if ($entryPoint) {
            $listener->replaceArgument(4, new Reference($entryPoint));
        }

        $listenerId .= '.'.$id;
        $container->setDefinition($listenerId, $listener);

        return $listenerId;
    }
}
