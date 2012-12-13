<?php
namespace KULeuven\ShibbolethBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;

interface ShibbolethUserProviderInterface extends UserProviderInterface{

	function createUser(ShibbolethUserToken $token);
}
