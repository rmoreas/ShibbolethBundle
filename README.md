# ShibbolethBundle

This bundle adds a shibboleth authentication provider for your Symfony2 project.

## Installation

Add Shibboleth bundle as dependency to the composer.json of your application

    "require": {
        ...
        "kuleuven/shibboleth-bundle": "dev-master"
        ...
    },

Then instantiate Bundle in your kernel init file

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
after run the command

```bash
    php app/console assets:install web/
```

to copy the resources to the projects web directory.

## Base configuration

