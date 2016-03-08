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

class Shibboleth
{

    private $handlerPath = '/Shibboleth.sso';
    private $securedHandler = true;
    private $sessionInitiatorPath = '/Login';
    private $usernameAttribute = 'Shib-Person-uid';
    private $attributeDefinitions = array(
        'uid'           => array('header'=> 'Shib-Person-uid', 'server' => 'uid', 'multivalue'=> false),
        'cn'            => array('header'=> 'Shib-Person-commonName', 'server' => 'cn', 'multivalue'=> false),
        'sn'            => array('header'=> 'Shib-Person-surname', 'server' => 'sn', 'multivalue'=> false),
        'givenName'     => array('header'=> 'Shib-Person-givenName', 'server' => 'givenName', 'multivalue'=> false),
        'mail'          => array('header'=> 'Shib-Person-mail', 'server' => 'mail', 'multivalue'=> true),
        'ou'            => array('header'=> 'Shib-Person-ou', 'server' => 'ou', 'multivalue'=> true),
        'telephoneNumber' => array('header'=> 'Shib-Person-telephoneNumber', 'server' => 'telephoneNumber', 'multivalue'=> true),
        'facsimileTelephoneNumber' => array('header'=> 'Shib-Person-facsimileTelephoneNumber', 'server' => 'facsimileTelephoneNumber', 'multivalue'=> true),
        'mobile'        => array('header'=> 'Shib-Person-mobile', 'server' => 'mobile', 'multivalue'=> true),
        'postalAddress' => array('header'=> 'Shib-Person-postalAddress', 'server' => 'postalAddress', 'multivalue'=> true),
        'affiliation'   => array('header'=> 'Shib-EP-UnscopedAffiliation', 'server' => 'affiliation', 'multivalue'=> true),
        'scopedAffiliation' => array('header'=> 'Shib-EP-ScopedAffiliation', 'server' => 'scopedAffiliation', 'multivalue'=> true),
        'orgUnitDN'     => array('header'=> 'Shib-EP-OrgUnitDN', 'server' => 'orgUnitDN', 'multivalue'=> true),
        'orgDN'         => array('header'=> 'Shib-EP-OrgDN', 'server' => 'orgDN', 'multivalue'=> false),
        'logoutURL'     => array('header'=> 'Shib-logoutURL', 'server' => 'logoutURL', 'multivalue'=> false),
        'identityProvider' => array('header'=> 'Shib-Identity-Provider', 'server' => 'Shib-Identity-Provider', 'multivalue'=> false),
        'originSite'    => array('header'=> 'Shib-Origin-Site', 'server' => 'originSite', 'multivalue'=> false),
        'authenticationInstant' => array('header'=> 'Shib-Authentication-Instant', 'server' => 'authenticationInstant', 'multivalue' => false),
        'employeeType' => array('header'=> 'Shib-KUL-employeeType', 'server' => 'employeeType', 'multivalue'=> false),
        'studentType' => array('header'=> 'Shib-KUL-studentType', 'server' => 'studentType', 'multivalue'=> true),
        'primouNumber' => array('header'=> 'Shib-KUL-PrimouNumber', 'server' => 'primouNumber', 'multivalue'=> true),
        'ouNumber' => array('header'=> 'Shib-KUL-ouNumber', 'server' => 'ouNumber', 'multivalue'=> true),
        'dipl' => array('header'=> 'Shib-KUL-dipl', 'server' => 'dipl', 'multivalue'=> true),
        'opl' => array('header'=> 'Shib-KUL-opl', 'server' => 'opl', 'multivalue'=> true),
        'campus' => array('header'=> 'Shib-KUL-campus', 'server' => 'campus', 'multivalue'=> false),
        'logoutURL' => array('header' => 'Shib-logoutURL', 'server' => 'Shib-logoutURL', 'multivalue' => false)
    );
    private $useHeaders = true;

    public function __construct($handlerPath, $sessionInitiatorPath, $securedHandler, $usernameAttribute, $attributeDefinitions = null, $useHeaders = true)
    {
        $this->handlerPath = $handlerPath;
        $this->sessionInitiatorPath = $sessionInitiatorPath;
        $this->securedHandler = $securedHandler;
        $this->usernameAttribute = $usernameAttribute;
        if (is_array($attributeDefinitions)) {
            foreach ($attributeDefinitions as $name => $def) {
                $def['alias'] = $name;
                $this->addAttributeDefinition($def);
            }
        }
        $this->useHeaders = $useHeaders;
    }

    public function getHandlerPath()
    {
        return $this->handlerPath;
    }

    public function isSecuredHandler()
    {
        return $this->securedHandler;
    }

    public function getSessionInitiatorPath()
    {
        return $this->sessionInitiatorPath;
    }

    private function getAttribute($request, $attribute)
    {
        if ($this->useHeaders) {
            return $request->headers->get(strtolower($this->attributeDefinitions[$attribute]['header']), null);
        } else {
            $value = $request->server->get($this->attributeDefinitions[$attribute]['server'], null);
            if ($value === null) {
                $value = $request->server->get(str_replace('-', '_', $this->attributeDefinitions[$attribute]['server']), null);
            }
            return $value;
        }
    }

    public function isAuthenticated(Request $request)
    {
        return (bool)$this->getAttribute($request, 'identityProvider');
    }

    public function getUser(Request $request)
    {
        return  $this->getAttribute($request, $this->usernameAttribute);
    }

    /**
     * Extract Shibboleth attributes from request
     * @param Request $request
     */
    public function getAttributes(Request $request)
    {
        $attributes = array();
        if ($this->isAuthenticated($request)) {
            foreach ($this->getAttributeDefinitions() as $name => $def) {
                $value = $this->getAttribute($request, $name);

                if (null === $value) {
                    //$this->attributes[$name] = array();
                } else {
                    if (@$def['charset'] == 'UTF-8') {
                        $value = utf8_decode($value);
                    }
                    $attributes[$name] = (@$def['multivalue'])? explode(';', $value) : $value;
                }
            }
        }
        return $attributes;
    }

    public function getAttributeDefinitions()
    {
        return $this->attributeDefinitions;
    }

    /**
     * Returns shibboleth session URL
     */
    public function getHandlerUrl(Request $request)
    {
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
    public function getLoginUrl(Request $request, $targetUrl = null)
    {
        // convert to absolute URL if not yet absolute.
        if (empty($targetUrl)) {
            $targetUrl = $request->getUri();
        }
        return $this->getHandlerURL($request) . $this->getSessionInitiatorPath() . '?target=' . urlencode($targetUrl);
    }

    /**
     * Returns URL to invalidate the shibboleth session.
     */
    public function getLogoutUrl(Request $request, $return = null)
    {
        $logout_redirect = $this->getAttribute($request, 'logoutURL');

        if (!empty($logout_redirect)) {
            return $this->getHandlerUrl($request) . '/Logout?return='. urlencode($logout_redirect
                    . (empty($return)? '' : '?return='.$return));
        } elseif (!empty($return)) {
            return $this->getHandlerUrl($request) . '/Logout?return='.urlencode($return);
        } else {
            return $this->getHandlerUrl($request) . '/Logout';
        }
    }

    public function addAttributeDefinition($def)
    {
        if (!isset($def['multivalue'])) {
            $def['multivalue'] = false;
        }
        if (!isset($def['charset'])) {
            $def['charset'] = 'ISO-8859-1';
        }
        if ($def['server'] === NULL) {
            $def['server'] = $def['alias'];
        }
        $this->attributeDefinitions[$def['alias']] = $def;
    }
}
