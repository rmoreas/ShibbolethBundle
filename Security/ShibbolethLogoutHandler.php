<?php
namespace KULeuven\ShibbolethBundle\Security;

use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use KULeuven\ShibbolethBundle\Service\Shibboleth;

class ShibbolethLogoutHandler implements LogoutHandlerInterface
{
    private $shibboleth;
    
    public function __construct(Shibboleth $shibboleth)
    {
        $this->shibboleth = $shibboleth;
    }
    
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        if ($token instanceof ShibbolethUserToken) {
            $request->getSession()->invalidate();
            return new RedirectResponse($this->shibboleth->getLogoutUrl($request));
        }        
        return $response;
    }
}
