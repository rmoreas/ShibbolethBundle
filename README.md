What is Shibboleth again?
==================
Sibboleth is a tool installed on the server, depending on the setup you can activate it with the .htaccess configuration as provided below or you may need to ask your server administration. If activated correctly it allows for your user to login at the provider used by your server, and afterwards adds additional header containing the authentication information on __each__ request, to be used by your application. You can test if all works nicely by simply dumping the headers: `dump($request->headers)`.  
The Shibboleth bundle aims to integrate this authentication fully into symfony; it adds firewalls and user managers to archive this. This fork simply makes the project compatible with symfony 3, and tries not to change existing behaviour. If an additional firewall is overkill for you, you might want to skip the installation of this Bundle, and rather set up listeners on kernel events which handle the authentification.  

ShibbolethBundle
================

This bundle adds a shibboleth authentication provider for your Symfony3 project.

Requirements
------------
* [PHP](http://php.net) 5.3.3 and up.
* [Symfony 3.0+][http://symfony.com]

Installation
------------

ShibbolethBundle is composer-friendly.

### 1. Add ShibbolethBundle in your composer.json

```js
    "require": {
        ...
        "kuleuven/shibboleth-bundle": "dev-master"
        ...
    },
   "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:rmoreas/ShibbolethBundle.git"
        }
    ],
```
Now tell composer to download the bundle by running the command:

```bash
    php composer.phar update kuleuven/shibboleth-bundle
```

Composer will install the bundle to your project's vendor/kuleuven directory..

### 2. Enable the bundle

Instantiate the bundle in your kernel:

```php
// app/AppKernel.php
<?php
    // ...
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new KULeuven\ShibbolethBundle\ShibbolethBundle(),
        );
    }
```

Configuration
-------------

### 1. Enable lazy shibboleth autentication in Apache

Add following lines to the .htaccess file in your projects web folder

```apache
    # web/.htaccess
	AuthType shibboleth
	ShibRequireSession Off
	ShibUseHeaders On
	require shibboleth
```

### 2. Setup authentication firewall

```yml
	# app/config/security.yml
	security:
		firewalls:
			secured_area:
				pattern:    ^/secured
				shibboleth: ~
                logout:
                    path: /secured/logout
                    target: /
                    success_handler: security.logout.handler.shibboleth
```

### 3. Shibboleth configuration

Possible configuration parameters are:

```yml
	# app/config/config.yml
	shibboleth:
		handler_path: /Shibboleth.sso
		secured_handler: true
		session_initiator_path: /Login
		username_attribute: uid
		use_headers: true
```

The above listed configuration values are the default values. To use the defaults, simply use the following line in your config:

```yml
	# app/config/config.yml
	shibboleth: ~
```

Available Shibboleth attributes
-------------------------------
By default, the bundle exposes several Shibboleth attributes through the user token, [ShibbolethUserToken](Security/ShibbolethUserToken.php). The token provides specific accessors for most of the attributes, as well as the generic accessors `getAttribute`, `getArrayAttribute` and `hasAttributeValue`. Each attribute is internally identified by an alias, which serves as argument to the aforementioned methods. The following table lists the Shibboleth attributes available (when provided) through the user token:

| Attribute                            | Alias                    |
| ------------------------------------ | ------------------------ |
| Shib-Person-uid                      | uid                      |
| Shib-Person-commonName               | cn                       |
| Shib-Person-surname                  | sn                       |
| Shib-Person-givenName                | givenName                |
| Shib-Person-mail                     | mail                     |
| Shib-Person-ou                       | ou                       |
| Shib-Person-telephoneNumber          | telephoneNumber          |
| Shib-Person-facsimileTelephoneNumber | facsimileTelephoneNumber |
| Shib-Person-mobile                   | mobile                   |
| Shib-Person-postalAddress            | postalAddress            |
| Shib-EP-UnscopedAffiliation          | affiliation              |
| Shib-EP-Scopedaffiliation            | scopedAffiliation        |
| Shib-EP-OrgunitDN                    | orgUnitDN                |
| Shib-EP-OrgDN                        | orgDN                    |
| Shib-logoutURL                       | logoutURL                |
| Shib-Identity-Provider               | identityProvider         |
| Shib-Origin-Site                     | originSite               |
| Shib-Authentication-Instant          | authenticationInstant    |
| Shib-KUL-employeeType                | employeeType             |
| Shib-KUL-studentType                 | studentType              |
| Shib-KUL-primouNumber                | primouNumber             |
| Shib-KUL-ouNumber                    | ouNumber                 |
| Shib-KUL-dipl                        | dipl                     |
| Shib-KUL-opl                         | opl                      |
| Shib-KUL-campus                      | campus                   |
| Shib-logoutURL                       | logoutURL                |

If for some reason you want to pass additional attributes (for example custom attributes) or overwrite existing, you can configure them this way:

```yml
# app/config/config.yml
shibboleth:
	# ...
	attribute_definitions:
		foo:  # the attribute alias
			header: shib-acme-foo  # the attribute name
		bar:
			header: shib-acme-bar
			multivalue: true  # attribute contains multiple values (default is false, i.e. attribute is scalar)
        identityProvider:
            header: REDIRECT_Shib-Identity-Provider # Change the existing attribute
            server: REDIRECT_Shib_Identity_Provider # Change the name of the variable with use_header option off
```

The key containing the configuration of each attribute will be its alias. That means the value(s) of the `shib-acme-foo` and `shib-acme-bar` attributes can be retrieved with:

```php
$foo = $token->getAttribute('foo');
$bars = $token->getArrayAttribute('bar'); // returns an array containing the multiple values
```

User Provider
-------------

This bundle doesn't include any User Provider, but you can implement your own.

If you store users in a database, they can be created on the fly when a users logs on for the first time on your application. Your UserProvider needs to implement the `KULeuven\ShibbolethBundle\Security\ShibbolethUserProviderInterface` interface.

### Example

This example uses Propel ORM to store users.

```php
	<?php
	namespace YourProjectNamespace\Security;

	use YourProjectNamespace\Model\User;
	use YourProjectNamespace\Model\UserQuery;

	use KULeuven\ShibbolethBundle\Security\ShibbolethUserProviderInterface;
	use KULeuven\ShibbolethBundle\Security\ShibbolethUserToken;

	use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
	use Symfony\Component\Security\Core\User\UserProviderInterface;
	use Symfony\Component\Security\Core\User\UserInterface;
	use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
	use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

	class UserProvider implements ShibbolethUserProviderInterface
	{
		public function loadUserByUsername($username)
		{
			$user = UserQuery::create()->findOneByUsername($username);
			if($user){
				return $user;
			} else{
				throw new UsernameNotFoundException("User ".$username. " not found.");
			}
		}

		public function createUser(ShibbolethUserToken $token){
			// Create user object using shibboleth attributes stored in the token.
			//
			$user = new User();
			$user->setUid($token->getUsername());
			$user->setSurname($token->getSurname());
			$user->setGivenName($token->getGivenName());
			$user->setMail($token->getMail());
			// If you like, you can also add default roles to the user based on shibboleth attributes. E.g.:
			if ($token->isStudent()) $user->addRole('ROLE_STUDENT');
			elseif ($token->isStaff()) $user->addRole('ROLE_STAFF');
			else $user->addRole('ROLE_GUEST');

			$user->save();
			return $user;
		}

		public function refreshUser(UserInterface $user)
		{
			if (!$user instanceof User) {
				throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
			}

			return $this->loadUserByUsername($user->getUsername());
		}

		public function supportsClass($class)
		{
			return $class === 'YourProjectNamespace\Model\User';
		}
	}
```
