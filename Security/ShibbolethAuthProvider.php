<?php
namespace KULeuven\ShibbolethBundle\Security;

use KULeuven\ShibbolethBundle\Service\Shibboleth;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class ShibbolethAuthProvider implements AuthenticationProviderInterface {

    private $userProvider;
    private $userChecker;
    private $defaultRoles;
    private $logger;
    
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, LoggerInterface $logger = null) {
        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->logger = $logger;
        $this->defaultRoles = array('ROLE_USER');
    }
    
    public function authenticate(TokenInterface $token) {

        if (!$this->supports($token)) { return null; }
        
        if (!$user = $token->getUser()) {
            throw new BadCredentialsException('No pre-authenticated shibboleth principal found in request.');
        }
        
        try {
            $user = $this->retrieveUser($token);

            $this->checkAuthentication($user, $token);
            $this->userChecker->checkPostAuth($user);
            
            $authenticatedToken = new ShibbolethUserToken($user, $token->getAttributes());
            $authenticatedToken->setAuthenticated(true);
	    if (null !== $this->logger) $this->logger->debug(sprintf('ShibbolethAuthProvider: authenticated token: %s',$authenticatedToken));
            return $authenticatedToken;
            
        } catch (UsernameNotFoundException $notFound) {
            throw $notFound;            
        }    
       
    }

    public function checkAuthentication($user,$token) {
        return true;        
    }
    
    public function retrieveUser($token) {
        try {
            $user = $this->userProvider->loadUserByUsername($token->getUsername());
	    if (null !== $this->logger) $this->logger->debug(sprintf('ShibbolethAuthProvider: userProvider returned: %s',$user->getUsername()));
        } catch (UsernameNotFoundException $e) {
            $user = new ShibbolethUser($token->getUsername(),$token->getAttributes(),$this->defaultRoles);
        }

        if (!$user instanceof UserInterface) {
            throw new AuthenticationServiceException('The user provider must return a UserInterface object.');
        }
        return $user;        
    }
    
    public function supports(TokenInterface $token) {
        return $token instanceof ShibbolethUserToken; 
    }
}
