<?php

namespace KULeuven\ShibbolethBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use KULeuven\ShibbolethBundle\DependencyInjection\Security\ShibbolethFactory;

class ShibbolethBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ShibbolethFactory());
    }    
}
