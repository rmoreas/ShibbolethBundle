<?php
namespace KULeuven\ShibbolethBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ShibbolethUserToken extends AbstractToken {

    /**
     * @param mixed $user
     * @param array $attributes 
     * @param array $roles
     * @throws \InvalidArgumentException
     */
    public function __construct($user = null, $attributes = array() ) {
        if ($user instanceof UserInterface) $roles = $user->getRoles();
        else $roles = array();
        parent::__construct($roles);
        $this->setUser($user);
        $this->setAttributes($attributes);
    }

    public function getCredentials() {
        return '';
    }

}
