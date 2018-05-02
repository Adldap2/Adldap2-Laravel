<h1 align="center">Adldap2 - Laravel</h1>

<p align="center">
 <a href="www.laravel.com">
  <img src="https://img.shields.io/badge/Built_for-Laravel-green.svg?style=flat-square">
 </a>
 <a href="https://travis-ci.org/Adldap2/Adldap2-Laravel">
  <img src="https://img.shields.io/travis/Adldap2/Adldap2-Laravel.svg?style=flat-square">
 </a>
 <a href="https://travis-ci.org/Adldap2/Adldap2-Laravel">
  <img src="https://img.shields.io/travis/Adldap2/Adldap2-Laravel.svg?style=flat-square">
 </a>
 <a href="https://scrutinizer-ci.com/g/Adldap2/Adldap2-Laravel">
  <img src="https://img.shields.io/scrutinizer/g/Adldap2/Adldap2-laravel/master.svg?style=flat-square">
 </a>
 <a href="https://packagist.org/packages/adldap2/adldap2-laravel">
  <img src="https://img.shields.io/packagist/dt/adldap2/adldap2-laravel.svg?style=flat-square">
 </a>
 <a href="https://packagist.org/packages/adldap2/adldap2-laravel">
  <img src="https://img.shields.io/packagist/v/adldap2/adldap2-laravel.svg?style=flat-square">
 </a>
 <a href="https://packagist.org/packages/adldap2/adldap2-laravel">
  <img src="https://img.shields.io/packagist/l/adldap2/adldap2-laravel.svg?style=flat-square">
 </a>
</p>

<p align="center">
Easy configuration, access, management and authentication to LDAP servers utilizing the root
 <a href="http://www.github.com/Adldap2/Adldap2">Adldap2</a> repository.
</p>

---

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

Then insert the facade:

```php
'Adldap' => Adldap\Laravel\Facades\Adldap::class
```

Publish the configuration file by running:

```bash
php artisan vendor:publish --tag="adldap"
```

Now you're all set!

## Usage

First, configure your LDAP connection in the `config/adldap.php` file.

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
