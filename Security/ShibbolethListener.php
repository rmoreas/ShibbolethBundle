<?php
namespace KULeuven\ShibbolethBundle\Security;

use KULeuven\ShibbolethBundle\Service\Shibboleth;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class ShibbolethListener implements ListenerInterface {

    private $securityContext;
    private $authenticationManager;
    private $providerKey;
    private $authenticationEntryPoint;
    private $logger;
    private $ignoreFailure;
    private $shibboleth;
    
    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, Shibboleth $shibboleth, $providerKey = null, AuthenticationEntryPointInterface $authenticationEntryPoint = null, LoggerInterface $logger = null) {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->authenticationEntryPoint = $authenticationEntryPoint;
        $this->logger = $logger;
        $this->ignoreFailure = false;
        $this->shibboleth = $shibboleth;
    }
        
    public function handle(GetResponseEvent $event) {

        $request = $event->getRequest();

                
        if (!$this->shibboleth->isAuthenticated($request)) { return; }

        
        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Checking security context token: %s', $this->securityContext->getToken()));
        }
            
        $user = $this->shibboleth->getUser($request);
        if (null !== $token = $this->securityContext->getToken()) {
            if ($token instanceof ShibbolethUserToken && $token->isAuthenticated() && $token->getUsername() === $user) {
                return;
            }
        }
        try {
            $attributes = $this->shibboleth->getAttributes($request);
            $token = $this->authenticationManager->authenticate(new ShibbolethUserToken($user, $attributes));

            if ($token instanceof TokenInterface) {
                if (null !== $this->logger) {
                    $this->logger->debug(sprintf('Authentication success: %s', $token));
                }    
                return $this->securityContext->setToken($token);
                
            } else if ($token instanceof Response) {
                return $event->setResponse($token);
            }

        } catch (AuthenticationException $e) {
            $this->securityContext->setToken(null);

            if (null !== $this->logger) {
                $this->logger->info(sprintf('Authentication request failed for user "%s": %s', $username, $e->getMessage()));
            }

            if ($this->ignoreFailure) { return;  }
            if ($this->authenticationEntryPoint) {
                return $event->setResponse($this->authenticationEntryPoint->start($request, $e));
            }
        }       
    }
}
