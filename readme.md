# Adldap2 - Laravel

![Built for Laravel](https://img.shields.io/badge/Built_for-Laravel-green.svg?style=flat-square)
[![Build Status](https://img.shields.io/travis/Adldap2/Adldap2-Laravel.svg?style=flat-square)](https://travis-ci.org/Adldap2/Adldap2-Laravel)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Adldap2/Adldap2-laravel/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/Adldap2/Adldap2-laravel/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)
[![Latest Stable Version](https://img.shields.io/packagist/v/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)
[![License](https://img.shields.io/packagist/l/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)

## Description

Adldap2 - Laravel allows easy configuration, access, management and authentication to LDAP connections utilizing the root
[Adldap2 Repository](http://www.github.com/Adldap2/Adldap2).

## Index

* [Installation](#installation)
* [Usage](#usage)
* [Auth Driver](#auth-driver)
  * [Installation](#installation-1)
  * [Usage](#usage-1)
  * [Features](#features)
    * [Synchronizing Attributes](#synchronizing-attributes)
    * [Binding to the User Model](#binding-the-adldap-user-model-to-the-laravel-user-model)
    * [Login Fallback](#login-fallback)
    * [Login Limitation Filter](#login-limitation-filter)
    * [Multiple Connections](#multiple-authentication-connections)
    * [Password Synchronization](#password-synchronization)
    * [Importing Users](#importing-users)
    * [Developing without an AD connection](#developing-locally-without-an-ad-connection)

## Installation

[Quick Start - From Scratch](docs/quick-start.md)

Insert Adldap2-Laravel into your `composer.json` file:

```json
"adldap2/adldap2-laravel": "3.0.*",
```

Or via command line:

```bash
composer require adldap2/adldap2-laravel
```

Then run `composer update`.

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

You can perform all methods on Adldap through its facade like so:
```php
// Finding a user.
$user = Adldap::search()->users()->find('john doe');

// Searching for a user.
$search = Adldap::search()->where('cn', '=', 'John Doe')->get();

// Authenticating against your LDAP server.
if (Adldap::auth()->attempt($username, $password)) {
    // Passed!
}

// Running an operation under a different connection:
$users = Adldap::getProvider('other-connection')->search()->users()->get();

// Creating a user.
$user = Adldap::make()->user([
    'cn' => 'John Doe',
]);

$user->save();
```

Or you can inject the Adldap contract:

```php
use Adldap\Contracts\AdldapInterface;

class UserController extends Controller
{
    /**
     * @var Adldap
     */
    protected $adldap;
    
    /**
     * Constructor.
     *
     * @param AdldapInterface $adldap
     */
    public function __construct(AdldapInterface $adldap)
    {
        $this->adldap = $adldap;
    }
    
    /**
     * Displays the all LDAP users.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = $this->adldap->search()->users()->get();
        
        return view('users.index', compact('users'));
    }
}
```

To see more usage in detail, please visit the [Adldap2 Repository](http://github.com/Adldap2/Adldap2);

## Auth Driver

The Adldap Laravel auth driver allows you to seamlessly authenticate AD users,
as well as have a local database record of the user.

This allows you to easily attach information to the users account
as you would a regular laravel application.

> **Note**: The Adldap auth driver actually extends from and utilizes Laravel's eloquent auth driver.

### Installation

#### Laravel 5.1

Insert the `AdldapAuthServiceProvider` into your `config/app.php` file:

```php
Adldap\Laravel\AdldapAuthServiceProvider::class,
```

Publish the auth configuration:

```bash
php artisan vendor:publish --tag="adldap"
```

Change the auth driver in `config/auth.php` to `adldap`:

```php
'driver' => 'adldap',
```

#### Laravel 5.2 & Up

Insert the `AdldapAuthServiceProvider` into your `config/app.php` file:

```php
Adldap\Laravel\AdldapAuthServiceProvider::class,
```

Publish the auth configuration:

```bash
php artisan vendor:publish --tag="adldap"
```

Open your `config/auth.php` configuration file and change the following:

Change the `driver` value inside the `users` authentication provider to `adldap`:

```php
'providers' => [
    'users' => [
        'driver' => 'adldap', // Changed from 'eloquent'
        'model' => App\User::class,
    ],
],
```

### Usage

#### Usernames

Inside your `config/adldap_auth.php` file there is a configuration option named `usernames`.

This array contains the `ldap` attribute you use for authenticating users, as well
as the `eloquent` attribute for locating the LDAP users model.

```php

'usernames' => [

    'ldap' => 'userprincipalname',
    
    'eloquent' => 'email',

],
```

If you're using a `username` field instead of `email` in your application, you will need to change this configuration.

> **Note**: Keep in mind you will also need to update your `database/migrations/2014_10_12_000000_create_users_table.php`
> migration to use a username field instead of email, **as well as** your LoginController.

For example, if you'd like to login users by their `samaccountname`:

```php

'usernames' => [

    'ldap' => 'samaccountname',
    
    'eloquent' => 'username',

],
```

Be sure to update the `sync_attributes` option to synchronize the users `username` as well:

```php
'sync_attributes' => [

    'username' => 'samaccountname', // Changed from 'email' => 'userprincipalname'
    'name' => 'cn',

],
```

#### Logging In

Login a user regularly using `Auth::attempt($credentials);`.

Once a user is authenticated, retrieve them as you would regularly:

```php
public function login(Request $request)
{
    if (Auth::attempt($request->only(['email', 'password'])) {
        
        // Returns \App\User model configured in `config/auth.php`.
        $user = Auth::user();
        
        
        return redirect()->to('home')
            ->withMessage('Logged in!');
    }
    
    return redirect()->to('login')
        ->withMessage('Hmm... Your username or password is incorrect');
}
```

## Features

#### Synchronizing Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `sync_attributes`. This is an array
of attributes where the key is the `User` model attribute, and the value is the active directory users attribute.

By default, the `User` models `name` attribute is synchronized to the AD users `cn` attribute. This means, upon login,
the users `name` attribute on Laravel `User` Model will be set to the active directory common name (`cn`) attribute, **then saved**.

Feel free to add more attributes here, however be sure that your `users` database table contains the key you've entered.

##### Sync Attribute Handlers

If you're looking to synchronize an attribute from an Adldap model that contains an array or an object, you can
use an attribute handler class to sync your model attributes manually. For example:

> **Note**: The class must contain a `handle` method. Otherwise you will receive an exception.

> **Tip**: Attribute handlers are constructed using the `app()` helper. This means you can type-hint any application
> dependencies you may need in the handlers constructor.

```php
'sync_attributes' => [
    
    App\Handlers\LdapAttributeHandler::class,

],
```

The `LdapAttributeHandler`:

```php
namespace App\Handlers;

use App\User as EloquentUser;
use Adldap\Models\User as LdapUser;

class LdapAttributeHandler
{
    /**
     * Synchronizes ldap attributes to the specified model.
     *
     * @param LdapUser     $ldapUser
     * @param EloquentUser $eloquentUser
     *
     * @return void
     */
    public function handle(LdapUser $ldapUser, EloquentUser $eloquentUser)
    {
        $eloquentUser->name = $ldapUser->getCommonName();
    }
}
```

> **Tip**: You do not need to call `save()` on your eloquent `User` model. It will be saved automatically.

#### Binding the Adldap User Model to the Laravel User Model

> **Note**: Before we begin, enabling this option will perform a single query on your AD server for a logged in user
**per request**. Eloquent already does this for authentication, however this could lead to slightly longer load times
(depending on your AD server and network speed of course).

To begin, insert the `Adldap\Laravel\Traits\HasLdapUser` trait onto your `User` model:

```php
namespace App;

use Adldap\Laravel\Traits\HasLdapUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes, HasLdapUser;
```

Now, after you've authenticated a user via the `adldap` driver, their LDAP model is available on their `User` model:

```php    
if (Auth::attempt($credentials)) {
    $user = Auth::user();
    
    var_dump($user); // Returns instance of App\User;
    
    var_dump($user->ldap); // Returns instance of Adldap\Models\User;
   
    // Examples:
    
    $user->ldap->getGroups();
    
    $user->ldap->getCommonName();
    
    $user->ldap->getConvertedSid();
}
```

#### Login Fallback

The login fallback option allows you to login as a local database user using the Eloquent authentication driver if 
active directory authentication fails. This option would be handy in environments where:
 
- You may have some active directory users and other users registering through the website itself (user does not exist in your AD).
- Local development where your AD server may be unavailable

To enable it, simply set the option to true in your `config/adldap_auth.php` configuration file:

```php
'login_fallback' => false, // Set to true.
```

#### Multiple Authentication Connections

To swap connections on the fly, set your configurations default connection and try re-authenticating the user:

```php
$auth = false;

if (Auth::attempt($credentials)) {
    $auth = true; // Logged in successfully
} else {
    // Login failed, swap and try other connection.
    Config::set('adldap_auth.connection', 'other-connection');
    
    if (Auth::attempt($credentials)) {
        $auth = true; // Passed logging in with other connection.
    }
}

if ($auth === true) {
    return redirect()
        ->to('dashboard')
        ->with(['message' => 'Successfully logged in!']);
}

return redirect()
        ->to('login')
        ->with(['message' => 'Your credentials are incorrect.']);
```

Or, if you'd like to all of your LDAP connections:

```php
$connections = config('adldap.connections');

foreach ($connections as $connection => $config) {

    // Set the LDAP connection to authenticate with.
    config(['adldap_auth.connection' => $connection]);

    if (Auth::attempt($credentials)) {
        return redirect()
            ->to('dashboard')
            ->with(['message' => 'Successfully logged in!']);
    }
}

return redirect()
        ->to('login')
        ->with(['message' => 'Your credentials are incorrect.']);
```

#### Password Synchronization

The password sync option allows you to automatically synchronize
users AD passwords to your local database. These passwords are
hashed natively by laravel.

Enabling this option would also allow users to login to their
accounts using the password last used when an AD connection
was present.

If this option is disabled, the local user account is applied
a random 16 character hashed password, and will lose access
to this account upon loss of AD connectivity.

This feature is enabled by default.

```php
'password_sync' => env('ADLDAP_PASSWORD_SYNC', true),
```

#### Developing Locally without an AD connection

You can continue to develop and login to your application without a connection to your AD server in the following scenario:

* You have `auto_connect` set to `false` in your `adldap.php` configuration
 > This is necessary so we don't automatically try and bind to your AD server when your application boots.

* You have `login_fallback` set to `true` in your `adldap_auth.php` configuration
 > This is necessary so we fallback to the standard `eloquent` auth driver.

* You have `password_sync` set to `true` in your `adldap_auth.php` configuration
 > This is necessary so we can login to the account with the last password that was used when an AD connection was present.

* You have logged into the synchronized LDAP account previously
 > This is necessary so the account actually exists in your local app's database.

If you have this configuration, you will have no issues developing an
application without a persistent connection to your LDAP server.
