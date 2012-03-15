<?php
namespace KULeuven\ShibbolethBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class ShibbolethUser implements UserInterface
{

    private $username;
    private $attributes = array();
    private $roles;
    
    public function __construct($username = null,array $attributes = array(), array $roles = array()) {
        $this->username = $username;
        $this->attributes = $attributes;
        $this->roles = $roles;
    }

    public function getRoles()
    {
        return $this->roles;
    }
    
    public function getPassword()
    {
        return null;
    }
    
    public function getSalt()
    {
        return null;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function isAuthenticated() {
                
    }
    
    public function eraseCredentials()
    {
    }

    public function equals(UserInterface $user)
    {
        if (!$user instanceof ShibbolethUser) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
        
    }

    
    /**
     * Returns name for display. Default is the 'cn' attribute or principal name if not available.
     */
    function getDisplayName() {
        return ($this->isAuthenticated())? (($this->hasAttribute('cn'))? $this->getAttribute('cn') : $this->getUsername()) : 'Anonymous';
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
        return $this->getAttributeValues('mail');
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
    
    function hasAffiliation($value) {
        return $this->hasAttribute('affiliation',$value);
    }
    
    function hasScopedAffiliation($value) {
        return $this->hasAttribute('scopedAffiliation',$value);
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
    function getAttribute($name) {
        return (array_key_exists($name, $this->attributes)) ? $this->attributes[$name][0] : null;
    }
    
    /**
     * Returns true if attribute exists with given value, or if attribute exists
     * if given value is null.
     */
    function hasAttribute($name, $value = null) {
        if (!array_key_exists($name, $this->attributes)) return false;
        return (empty($value))? true : (array_search($value, $this->attributes[$name]) !== false);
    }
    
    /**
     * Returns array of attribute values for given attribute.
     */
    function getAttributeValues($name) {
        return @$this->attributes[$name];
    }
    
    /**
     * Returns all principal attributes as an array. Alias for toArray();
     */
    function getAttributes() {
        return $this->attributes;
    }    
}
