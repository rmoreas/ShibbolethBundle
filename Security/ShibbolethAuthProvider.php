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
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ShibbolethAuthProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $userChecker;
    private $providerKey;
    private $defaultRoles;
    private $logger;

    public function __construct(
        UserProviderInterface $userProvider,
        UserCheckerInterface $userChecker,
        $providerKey,
        LoggerInterface $logger = null
    ) {
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
        $this->logger = $logger;
        $this->defaultRoles = array('ROLE_USER');
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return null;
        }

        if (!$user = $token->getUser()) {
            throw new BadCredentialsException('No pre-authenticated shibboleth principal found in request.');
        }

        try {
            $user = $this->retrieveUser($token);

            $this->checkAuthentication($user, $token);
            if ($user instanceof UserInterface) {
                $this->userChecker->checkPostAuth($user);
            }

            $authenticatedToken = new ShibbolethUserToken($this->providerKey, $user, $token->getAttributes());
            $authenticatedToken->setAuthenticated(true);
            if (null !== $this->logger) {
                $this->logger
                        ->debug(sprintf(
                            'ShibbolethAuthProvider: authenticated token: %s',
                            $authenticatedToken
                        ));
            }
            return $authenticatedToken;
        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;
        }
    }

    public function checkAuthentication($user, $token)
    {
        return true;
    }

    public function retrieveUser($token)
    {
        try {
            $user = $this->userProvider
                    ->loadUserByUsername($token->getUsername());
            if (null !== $this->logger) {
                $this->logger
                        ->debug(sprintf(
                            'ShibbolethAuthProvider: userProvider returned: %s',
                            $user->getUsername()
                        ));
            }

            if (!$user instanceof UserInterface) {
                throw new AuthenticationServiceException(
                    'The user provider must return a UserInterface object.'
                );
            }
        } catch (UsernameNotFoundException $notFound) {
            if ($this->userProvider instanceof ShibbolethUserProviderInterface) {
                $user = $this->userProvider->createUser($token);
                if ($user === null) {
                    $user = $token->getUsername();
                }
            } else {
                throw $notFound;
            }
        }

        return $user;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof ShibbolethUserToken
                && $this->providerKey == $token->getProviderKey();
    }
}
