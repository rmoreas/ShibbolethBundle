<?php
namespace KULeuven\ShibbolethBundle\DependencyInjection\Security;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class ShibbolethFactory implements SecurityFactoryInterface {
    
 
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
            ->end()
        ;
    }
    
    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        if (null !== $defaultEntryPoint) {
            return $defaultEntryPoint;
        }

        $entryPointId = 'security.authentication.entry_point.shibboleth.'.$id;
        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('security.authentication.entry_point.shibboleth'))
        ;

        return $entryPointId;
    }
    
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'security.authentication.provider.shibboleth.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('security.authentication.provider.shibboleth'))
            ->replaceArgument(0, new Reference($userProviderId))
        //    ->replaceArgument(2, $id)
        ;
        return $providerId;
    }

    protected function createListener($container, $id, $config, $userProvider,$entryPoint)
    {    
        $listenerId = 'security.authentication.listener.shibboleth';
        $listener = new DefinitionDecorator($listenerId);
        $listener
            ->replaceArgument(3, $id)
            ->replaceArgument(4, new Reference($entryPoint))
        ;       
        $listenerId .= '.'.$id;
        $container->setDefinition($listenerId, $listener);
        
        return $listenerId;
    }

}
