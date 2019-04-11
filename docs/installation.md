# Requirements

Adldap2-Laravel requires the following:

- Laravel 5.5
- PHP 7.1 or greater
- PHP LDAP extension enabled
- An LDAP Server

# Composer

Run the following command in the root of your project:

```bash
composer require adldap2/adldap2-laravel
```

> **Note**: If you are using laravel 5.5 or higher you can skip the service provider
> and facade registration and continue with publishing the configuration file.

Once finished, insert the service provider in your `config/app.php` file:

```php
Adldap\Laravel\AdldapServiceProvider::class,
```

Then insert the facade alias (if you're going to use it):

```php
'Adldap' => Adldap\Laravel\Facades\Adldap::class
```

Finally, publish the `ldap.php` configuration file by running:

```bash
php artisan vendor:publish --provider "Adldap\Laravel\AdldapServiceProvider"
```

Now you're all set! You're ready to move onto [setup](setup.md).