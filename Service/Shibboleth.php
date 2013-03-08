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
 namespace KULeuven\ShibbolethBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class Shibboleth {
    
    private $handlerPath = '/Shibboleth.sso';
    private $securedHandler = true;
    private $sessionInitiatorPath = '/Login';
    private $usernameAttribute = 'shib-person-uid';
    private $attributeDefinitions = array(
        'uid'           => array('header'=> 'shib-person-uid', 'multivalue'=> false),
        'cn'            => array('header'=> 'shib-person-commonname', 'multivalue'=> false, 'charset'=> 'UTF-8'),
        'sn'            => array('header'=> 'shib-person-surname', 'multivalue'=> false, 'charset'=> 'UTF-8'),
        'givenName'     => array('header'=> 'shib-person-givenname', 'multivalue'=> false, 'charset'=> 'UTF-8'),
        'mail'          => array('header'=> 'shib-person-mail', 'multivalue'=> true),
        'ou'            => array('header'=> 'shib-person-ou', 'multivalue'=> true),
        'telephoneNumber' => array('header'=> 'shib-person-telephonenumber', 'multivalue'=> true),
        'facsimileTelephoneNumber' => array('header'=> 'shib-person-facsimiletelephonenumber', 'multivalue'=> true),
        'mobile' 		=> array('header'=> 'shib-person-mobile', 'multivalue'=> true),
        'postalAddress' => array('header'=> 'shib-person-postaladdress', 'multivalue'=> true),
        'affiliation'   => array('header'=> 'shib-ep-unscopedaffiliation', 'multivalue'=> true),
        'scopedAffiliation' => array('header'=> 'shib-ep-scopedaffiliation', 'multivalue'=> true),
        'orgUnitDN'     => array('header'=> 'shib-ep-orgunitdn', 'multivalue'=> true),
        'orgDN'         => array('header'=> 'shib-ep-orgdn', 'multivalue'=> false),
        'logoutURL'     => array('header'=> 'shib-logouturl', 'multivalue'=> false),
        'identityProvider' => array('header'=> 'shib-identity-provider', 'multivalue'=> false),
        'originSite'    => array('header'=> 'shib-origon-site', 'multivalue'=> false),
        'authenticationInstant' => array('header'=> 'shib-authentication-instant', 'multivalue' => false),
        'employeeType' => array('header'=> 'shib-kul-employeetype', 'multivalue'=> false),
        'studentType' => array('header'=> 'shib-kul-studenttype', 'multivalue'=> true),
        'primouNumber' => array('header'=> 'shib-kul-primounumber', 'multivalue'=> true),
        'ouNumber' => array('header'=> 'shib-kul-ounumber', 'multivalue'=> true),
        'dipl' => array('header'=> 'shib-kul-dipl', 'multivalue'=> true),
        'opl' => array('header'=> 'shib-kul-opl', 'multivalue'=> true),
        'campus' => array('header'=> 'shib-kul-campus', 'multivalue'=> false)
    );
    
    public function __construct($handlerPath,$sessionInitiatorPath, $securedHandler, $usernameAttribute, $attributeDefinitions = null) {
        $this->handlerPath = $handlerPath;
        $this->sessionInitiatorPath = $sessionInitiatorPath;
        $this->securedHandler = $securedHandler;
        $this->usernameAttribute = $usernameAttribute;
        if (is_array($attributeDefinitions)) {
            foreach($attributeDefinitions as $name => $def) {
                $def['alias'] = $name;
                $this->addAttributeDefinition($def);
            }
        }
    }

    public function getHandlerPath() {
        return $this->handlerPath;
    }
    
    public function isSecuredHandler() {
        return $this->securedHandler;
    }
    
    public function getSessionInitiatorPath() {
        return $this->sessionInitiatorPath;
    }

    public function isAuthenticated(Request $request) {
        return (bool)$request->headers->get('shib-identity-provider');
    }

    public function getUser(Request $request) {
        return  $request->headers->get($this->usernameAttribute, null);
    }
    
    /**
     * Extract Shibboleth attributes from request
     * @param Request $request
     */
    public function getAttributes(Request $request) {
        $attributes = array();
        if ($this->isAuthenticated($request)) {
            foreach ($this->getAttributeDefinitions() as $name => $def) {
                $value = $request->headers->get($def['header'],null);
                if (null === $value) {
                    //$this->attributes[$name] = array();
                } else {
                    if(@$def['charset'] == 'UTF-8') $value = utf8_decode($value);
                    $attributes[$name] = (@$def['multivalue'])? explode(';',$value) : $value;
                }
            }
        }
        return $attributes;
    }

    function getAttributeDefinitions()
    {
        return $this->attributeDefinitions;
    }

    /**
     * Returns shibboleth session URL
     */
    function getHandlerUrl(Request $request) {
        return (($this->isSecuredHandler())? 'https://' : 'http://' )
            . $request->getHost()
            . $this->handlerPath;
    }
    
    /**
     * Returns URL to initiate login session. After successfull login, the user will be redirected
     * to the optional target page. The target can be an absolute or relative URL.
     *
     * @param string $targetUrl URL to redirect to after successfull login. Defaults to the current request URL.
     * @return string           The absolute URL to initiate a session
     */
    function getLoginUrl(Request $request, $targetUrl = null) {
        // convert to absolute URL if not yet absolute.
        if (empty($targetUrl)) $targetUrl = $request->getUri();
        return $this->getHandlerURL($request) . $this->getSessionInitiatorPath() . '?target=' . urlencode($targetUrl);
    }
    
    /**
     * Returns URL to invalidate the shibboleth session.
     */
    function getLogoutUrl(Request $request, $return = null) {
        $logout_redirect = $request->headers->get('shib-logouturl');
        if (!empty($logout_redirect)) {
            return $this->getHandlerUrl($request) . '/Logout?return='. urlencode($logout_redirect
                    . (empty($return)? '' : '?return='.$return) );
        } elseif (!empty($return)) {
            return $this->getHandlerUrl($request) . '/Logout?return='.urlencode($return);
        } else {
            return $this->getHandlerUrl($request) . '/Logout';
        }
    }

    function addAttributeDefinition($def) {
        if (!isset($def['multivalue'])) $def['multivalue'] = false;
        if (!isset($def['charset'])) $def['charset'] = 'ISO-8859-1';
        $this->attributeDefinitions[$def['alias']] = $def;
    }    
    
}
