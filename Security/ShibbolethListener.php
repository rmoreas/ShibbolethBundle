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
namespace KULeuven\ShibbolethBundle\Security;

use KULeuven\ShibbolethBundle\Service\Shibboleth;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShibbolethListener implements ListenerInterface {

    private $securityContext;
    private $authenticationManager;
    private $providerKey;
    private $authenticationEntryPoint;
    private $logger;
    private $ignoreFailure;
    private $dispatcher;
    private $shibboleth;
    
    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, Shibboleth $shibboleth, $providerKey = null, AuthenticationEntryPointInterface $authenticationEntryPoint = null, LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null) {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->authenticationEntryPoint = $authenticationEntryPoint;
        $this->logger = $logger;
        $this->ignoreFailure = false;
        $this->dispatcher = $dispatcher;
        $this->shibboleth = $shibboleth;
    }
        
    public function handle(GetResponseEvent $event) {

        $request = $event->getRequest();

        /*        
        if (!$this->shibboleth->isAuthenticated($request)) { return; }
        */
        
        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Checking security context token: %s', $this->securityContext->getToken()));
        }
            
        $username = $this->shibboleth->getUser($request);
        
        if ('' == $username) {
            return;
        }
        
        if (null !== $this->logger) $this->logger->debug(sprintf('Shibboleth service returned user: %s', $username));
        if (null !== $token = $this->securityContext->getToken()) {
            if ($token instanceof ShibbolethUserToken && $token->isAuthenticated()) {
                if ( $token->getUsername() === $username) return;
            } elseif ($token->isAuthenticated()) {
                return;
            }
        }
        try {
            $attributes = $this->shibboleth->getAttributes($request);
            $this->logger->debug(sprintf('Shibboleth returned attributes from: %s', @$attributes['identityProvider'][0]));
            $token = $this->authenticationManager->authenticate(new ShibbolethUserToken($username, $attributes));
            
            if (null !== $this->logger) $this->logger->debug(sprintf('ShibbolethListener: received token: %s', $token));

            if ($token instanceof TokenInterface) {
                if (null !== $this->logger) {
                    $this->logger->debug(sprintf('Authentication success: %s', $token));
                }    
                $this->securityContext->setToken($token);

                if (null !== $this->dispatcher) {
                    $loginEvent = new InteractiveLoginEvent($request, $token);
                    $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
                }
            } else if ($token instanceof Response) {
                $event->setResponse($token);
            }

        } catch (AuthenticationException $e) {
            $this->securityContext->setToken(null);

            if (null !== $this->logger) {
                $this->logger->info(sprintf('Shibboleth authentication request failed for user "%s": %s', $username, $e->getMessage()));
            }

            if ($this->authenticationEntryPoint) {
                return $event->setResponse($this->authenticationEntryPoint->start($request, $e));
            }
        }       
    }
}
