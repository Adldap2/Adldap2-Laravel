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

## Quick Start

Install Adldap2-Laravel via [composer](https://getcomposer.org/) using the command:

```bash
composer require adldap2/adldap2-laravel
```

Publish the configuration file using:

```bash
php artisan vendor:publish --provider="Adldap\Laravel\AdldapServiceProvider"
```

Configure your LDAP connection in the published `ldap.php` file.

Then, use the `Adldap\Laravel\Facades\Adldap` facade: 

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

**Or** inject the `Adldap\AdldapInterface`:

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
