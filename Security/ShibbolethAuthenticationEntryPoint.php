<?php
namespace KULeuven\ShibbolethBundle\Security;

use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use KULeuven\ShibbolethBundle\Service\Shibboleth;

class ShibbolethAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $shibboleth;
    
    public function __construct(Shibboleth $shibboleth)
    {
        $this->shibboleth = $shibboleth;
    }
    
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $response = new RedirectResponse($this->shibboleth->getLoginUrl($request));        
        return $response;
    }
}
