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
  * [Setup](#setup)
  * Features
    * [Providers](docs/auth/providers.md)
    * [Synchronizing Attributes](docs/auth/syncing.md)
    * [Binding to the User Model](docs/auth/binding.md)
    * [Login Fallback](docs/auth/fallback.md)
    * [Multiple Connections](docs/auth/multiple-connections.md)
    * [Password Synchronization](docs/auth/syncing/#password-synchronization)
    * [Importing Users](docs/importing.md)
    * [Developing without an AD connection](docs/auth/fallback.md#developing-locally-without-an-ad-connection)

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

### Setup

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
