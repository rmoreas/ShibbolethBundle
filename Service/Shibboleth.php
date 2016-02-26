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
        'uid'           => array('header'=> 'Shib-Person-uid', 'multivalue'=> false),
        'cn'            => array('header'=> 'Shib-Person-commonName', 'multivalue'=> false),
        'sn'            => array('header'=> 'Shib-Person-surname', 'multivalue'=> false),
        'givenName'     => array('header'=> 'Shib-Person-givenName', 'multivalue'=> false),
        'mail'          => array('header'=> 'Shib-Person-mail', 'multivalue'=> true),
        'ou'            => array('header'=> 'Shib-Person-ou', 'multivalue'=> true),
        'telephoneNumber' => array('header'=> 'Shib-Person-telephoneNumber', 'multivalue'=> true),
        'facsimileTelephoneNumber' => array('header'=> 'Shib-Person-facsimileTelephoneNumber', 'multivalue'=> true),
        'mobile'        => array('header'=> 'Shib-Person-mobile', 'multivalue'=> true),
        'postalAddress' => array('header'=> 'Shib-Person-postalAddress', 'multivalue'=> true),
        'affiliation'   => array('header'=> 'Shib-EP-UnscopedAffiliation', 'multivalue'=> true),
        'scopedAffiliation' => array('header'=> 'Shib-EP-ScopedAffiliation', 'multivalue'=> true),
        'orgUnitDN'     => array('header'=> 'Shib-EP-OrgUnitDN', 'multivalue'=> true),
        'orgDN'         => array('header'=> 'Shib-EP-OrgDN', 'multivalue'=> false),
        'logoutURL'     => array('header'=> 'Shib-logoutURL', 'multivalue'=> false),
        'identityProvider' => array('header'=> 'Shib-Identity-Provider', 'multivalue'=> false),
        'originSite'    => array('header'=> 'Shib-Origin-Site', 'multivalue'=> false),
        'authenticationInstant' => array('header'=> 'Shib-Authentication-Instant', 'multivalue' => false),
        'employeeType' => array('header'=> 'Shib-KUL-employeeType', 'multivalue'=> false),
        'studentType' => array('header'=> 'Shib-KUL-studentType', 'multivalue'=> true),
        'primouNumber' => array('header'=> 'Shib-KUL-PrimouNumber', 'multivalue'=> true),
        'ouNumber' => array('header'=> 'Shib-KUL-ouNumber', 'multivalue'=> true),
        'dipl' => array('header'=> 'Shib-KUL-dipl', 'multivalue'=> true),
        'opl' => array('header'=> 'Shib-KUL-opl', 'multivalue'=> true),
        'campus' => array('header'=> 'Shib-KUL-campus', 'multivalue'=> false)
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
            return $request->headers->get(strtolower($attribute), null);
        } else {
            $value = $request->server->get($attribute, null);
            if ($value === null) {
                $value = $request->server->get(str_replace('-', '_', $attribute), null);
            }
            return $value;
        }
    }

    public function isAuthenticated(Request $request)
    {
        return (bool)$this->getAttribute($request, 'Shib-Identity-Provider');
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
                if ($this->useHeaders) {
                    $value = $this->getAttribute($request, $def['header']);
                } else {
                    $value = $this->getAttribute($request, $name);
                }

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
        $logout_redirect = $this->getAttribute($request, 'Shib-logoutURL');
        
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
        $this->attributeDefinitions[$def['alias']] = $def;
    }
}
