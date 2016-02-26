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
