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

use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ShibbolethUserToken extends AbstractToken {

    /**
     * @param mixed $user
     * @param array $attributes 
     * @param array $roles
     * @throws \InvalidArgumentException
     */
    public function __construct($user = null, $attributes = array(), $roles = array() ) {
        if (empty($roles) && $user instanceof UserInterface) $roles = $user->getRoles();
        parent::__construct($roles);
        $this->setUser($user);
        $this->setAttributes($attributes);
    }

    public function getCredentials() {
        return '';
    }

    /**
     * Returns name for display. Default is the 'cn' attribute or principal name if not available.
     */
    function getDisplayName() {
        return ($this->hasAttribute('cn'))? $this->getAttribute('cn') : $this->getUsername();
    }
    
    /**
     * Returns common name of principal. This is an alias for the 'cn' attribute
     */
    function getCommonName() {
        return $this->getAttribute('cn');
    }
    
    /**
     * Returns full name of principal. This is an alias for commonName
     */
    function getFullName() {
        return $this->getAttribute('cn');
    }
    
    function getSurname() {
        return $this->getAttribute('sn');
    }
    
    function getGivenName() {
        return $this->getAttribute('givenName');
    }
    
    function getMail() {
        return $this->getAttribute('mail');
    }
    
    function getMails() {
        return $this->getArrayAttribute('mail');
    }
    
    function getUID() {
        return $this->getAttribute('uid');
    }
    
    function getAffiliation() {
        return $this->getAttribute('affiliation');
    }
    
    function getScopedAffiliation() {
        return $this->getAttribute('scopedAffiliation');
    }
    
    function hasAffiliation($value = null) {
        return $this->hasAttributeValue('affiliation',$value);
    }
    
    function hasScopedAffiliation($value = null) {
        return $this->hasAttributeValue('scopedAffiliation',$value);
    }
    
    function isMember($scope = null) {
        return (empty($scope))? $this->hasAffiliation('member'): $this->hasScopedAffiliation('member@'.$scope);
    }
    
    function isEmployee($scope = null) {
        return (empty($scope))? $this->hasAffiliation('employee'): $this->hasScopedAffiliation('employee@'.$scope);
    }
    
    function isStudent($scope = null) {
        return (empty($scope))? $this->hasAffiliation('student'): $this->hasScopedAffiliation('student@'.$scope);
    }
    
    function isStaff($scope = null) {
        return (empty($scope))? $this->hasAffiliation('staff'): $this->hasScopedAffiliation('staff@'.$scope);
    }
    
    function isFaculty($scope = null) {
        return (empty($scope))? $this->hasAffiliation('faculty'): $this->hasScopedAffiliation('faculty@'.$scope);
    }
    
    function getLogoutURL() {
        return $this->getAttribute('logoutURL');
    }
    
    /**
     * Returns attribute value. If it's a multivalue, the first value is returned
     */
    public function getAttribute($name) {
        $value = parent::getAttribute($name);
        return (is_array($value)) ? $value[0] : $value;
    }

    /**
     * Returns an attribute as an array of values
     * @param string $name
     * @return array
     */
    public function getArrayAttribute($name) {
        $value = parent::getAttribute($name);
        return (is_array($value)) ? $value : array($value);
    }
    
    /**
     * Returns true if attribute exists with given value, or if attribute exists
     * if given value is null.
     */
    function hasAttributeValue($name, $value = null) {
        if (!$this->hasAttribute($name)) return false;
        return (empty($value))? true : (array_search($value, $this->getArrayAttribute($name)) !== false);
    }
    
       
}
