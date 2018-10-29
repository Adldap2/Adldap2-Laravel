# Introduction

## What is Adldap2-Laravel?

Adldap2-Laravel is an extension to the core [Adldap2](https://github.com/Adldap2/Adldap2) package.

This package allows you to:

1. Easily configure and manage multiple LDAP connections at once
2. Authenticate LDAP users into your Laravel application
3. Import / Synchronize LDAP users into your database and easily keep them up to date with changes in your directory
4. Search your LDAP directory with a fluent and easy to use query builder
5. Create / Update / Delete LDAP entities with ease
6. And more

## Index

* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
* Auth Driver
  * [Installation & Basic Setup](docs/auth.md#installation)
  * [Quick Start - From Scratch](docs/quick-start.md)
  * [Upgrading](docs/auth.md#upgrading-from-3-to-4)
  * [Features](docs/auth.md#features)
    * [Providers](docs/auth.md#providers)
    * [Scopes](docs/auth.md#scopes)
    * [Rules](docs/auth.md#rules)
    * [Events](docs/auth.md#events)
    * [Synchronizing Attributes](docs/auth.md#syncing-attributes)
    * [Model Binding](docs/auth.md#model-binding)
    * [Login Fallback](docs/auth.md#fallback)
    * [Single Sign On (SSO) Middleware](docs/auth.md#middleware)
    * [Password Synchronization](docs/auth.md#password-synchronization)
    * [Importing Users](docs/importing.md)

## Requirements

To use Adldap2-Laravel, your application and server must meet the following requirements:

- Laravel 5.*
- PHP 7.0 or greater
- PHP LDAP extension enabled
- An LDAP Server

## Installation

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

Then insert the facade (if you're going to use it):

```php
'Adldap' => Adldap\Laravel\Facades\Adldap::class
```

Finally, publish the `ldap.php` configuration file by running:

```bash
php artisan vendor:publish --provider="Adldap\Laravel\AdldapServiceProvider"
```

Now you're all set!

## Usage

First, configure your LDAP connection in the `config/ldap.php` file.

Then, you can perform methods on your default connection through the `Adldap` facade like so:

```php
use Adldap\Laravel\Facades\Adldap;

// Finding a user:
$user = Adldap::search()->users()->find('john doe');

// Searching for a user:
$search = Adldap::search()->where('cn', '=', 'John Doe')->get();

// Running an operation under a different connection:
$users = Adldap::getProvider('other-connection')->search()->users()->get();

// Creating a user:
$user = Adldap::make()->user([
    'cn' => 'John Doe',
]);

// Modifying Attributes:
$user->cn = 'Jane Doe';

// Saving a user:
$user->save();
```

If you do not specify an alternate connection using `getProvider()`, your
`default` connection will be utilized for all methods.

Upon performing operations without specifying a connection, your default
connection will be connected to and bound automatically
using your configured username and password.

If you would prefer, you can also inject the Adldap interface into your controllers,
which gives you access to all of your LDAP connections and resources as the facade.

```php
use Adldap\AdldapInterface;

class UserController extends Controller
{
    /**
     * @var Adldap
     */
    protected $ldap;
    
    /**
     * Constructor.
     *
     * @param AdldapInterface $adldap
     */
    public function __construct(AdldapInterface $ldap)
    {
        $this->ldap = $ldap;
    }
    
    /**
     * Displays the all LDAP users.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = $this->ldap->search()->users()->get();
        
        return view('users.index', compact('users'));
    }
    
    /**
     * Displays the specified LDAP user.
     *
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $user = $this->ldap->search()->findByGuid($id);
        
        return view('users.show', compact('user'));
    }
}
```

To see more usage in detail, please visit the [Adldap2](http://github.com/Adldap2/Adldap2) repository.

## Versioning

Adldap2-Laravel is versioned under the [Semantic Versioning](http://semver.org/) guidelines as much as possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

* Breaking backward compatibility bumps the major and resets the minor and patch.
* New additions without breaking backward compatibility bumps the minor and resets the patch.
* Bug fixes and misc changes bumps the patch.

Minor versions are not maintained individually, and you're encouraged to upgrade through to the next minor version.

Major versions are maintained individually through separate branches.
