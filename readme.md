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
    * [Windows Authentication - SSO](#windows-authentication-sso-middleware)
    * [Login Limitation Filter](#login-limitation-filter)
    * [Multiple Connections](#multiple-authentication-connections)
    * [Password Synchronization](#password-synchronization)
    * [Importing Users](#importing-users)
    * [Developing without an AD connection](#developing-locally-without-an-ad-connection)
  * [Providers](#auth-providers)
    * [DatabaseUserProvider](#databaseuserprovider)
    * [NoDatabaseUserProvider](#nodatabaseuserprovider)

## Installation

[Quick Start - From Scratch](quick-start.md)

Insert Adldap2-Laravel into your `composer.json` file:

```json
"adldap2/adldap2-laravel": "2.1.*",
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

// Authenticating.
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
        $users = $this->adldap->getDefaultProvider()->search()->users()->get();
        
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
/*
|--------------------------------------------------------------------------
| Default Authentication Driver
|--------------------------------------------------------------------------
|
| This option controls the authentication driver that will be utilized.
| This driver manages the retrieval and authentication of the users
| attempting to get access to protected areas of your application.
|
| Supported: "database", "eloquent"
|
*/

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

#### Username Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `username_attribute`. The key of the
array indicates the input name of your login form, and the value indicates the LDAP attribute that this references.

This option just allows you to set your input name to however you see fit, and allow different ways of logging in a user.

> **Note**: The actual authentication against your AD server is done with
> the `login_attribute` inside your `config/adldap_auth.php` file. The
> `username_attribute` array is used for **locating** your user in AD.

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

##### Logging in Users Via Their Username

If you need to login users via their username (`samaccountname`), then you'll need to modify a couple things.
Otherwise, if you're logging in users via email, then you don't need to change a thing and you can
skip over the following documentation.

First make sure you have the `username` field inside your users database table as well.
By default, laravel's migrations use the `email` field:

```php
// 2014_10_12_000000_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name');
    
    // Changed to `username` instead of `email`.
    $table->string('username')->unique();
    
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```
 
Inside `config/adldap_auth.php`, change the array to the following:

```php
'username_attribute' => ['username' => 'samaccountname'],
```

In your login form, change the username form input name to your configured
input name (by default this is set to `email`):

```html
<!-- Changed the `name` attribute to `username`. -->
<input type="text" name="username" />
```

You'll also need to add the following to your AuthController / LoginController if
you're not already overriding the default postLogin method:

```php
// In Laravel <= 5.2
protected $username = 'username';

// In Laravel >= 5.3
public function username()
{
    return 'username';
}
```

Once you've followed these steps, you'll be able to authenticate LDAP users by their username instead of their email.

## Features

#### Synchronizing Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `sync_attributes`. This is an array
of attributes where the key is the `User` model attribute, and the value is the active directory users attribute.

By default, the `User` models `name` attribute is synchronized to the AD users `cn` attribute. This means, upon login,
the users `name` attribute on Laravel `User` Model will be set to the active directory common name (`cn`) attribute, **then saved**.

Feel free to add more attributes here, however be sure that your `users` database table contains the key you've entered.

##### Sync Attribute Handlers

> **Note**: This feature was introduced in `v1.3.8` and updated in `v3.0.0`.

> **Tip**: Attribute handlers are constructed using the `app()` helper. This means you can type-hint any application
> dependencies you may need in the handlers constructor.


###### Adldap2-Laravel Version 2.1 and lower

If you're looking to synchronize an attribute from an Adldap model that contains an array or an object, you can
use a class callback to return a specific value to your Laravel model's attribute. For example:

```php
'sync_attributes' => [

    'name' => 'App\Handlers\LdapAttributeHandler@name',

],
```

The `LdapAttributeHandler` class:

```php
namespace App\Handlers;

use Adldap\Models\User;

class LdapAttributeHandler
{
    /**
     * Returns the common name of the AD User.
     *
     * @param User $user
     *
     * @return string
     */
    public function name(User $user)
    {
        return $user->getAccountName();
    }
}
```

###### Adldap2-Laravel Version 3.0 and higher

You can now use classes to sync your AD attributes to your Eloquent model. For example:

> **Note**: The class must contain a `handle` method. Otherwise you will receive an exception.


```php
'sync_attributes' => [
    
    \App\Handlers\LdapAttributeHandler::class,

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
     * @param User  $user
     * @param Model $model
     *
     * @return void
     */
    public function handle(LdapUser $ldapUser, EloquentUser $eloquentUser)
    {
        $eloquentUser->name = $ldapUser->getCommonName();
    }
}
```


#### Binding the Adldap User Model to the Laravel User Model

> **Note**: Before we begin, enabling this option will perform a single query on your AD server for a logged in user
**per request**. Eloquent already does this for authentication, however this could lead to slightly longer load times
(depending on your AD server and network speed of course).

Inside your `config/adldap_auth.php` file there is a configuration option named `bind_user_to_model`. Setting this to
true sets the `adldapUser` property on your configured auth User model to the Adldap User model. For example:
```php    
if (Auth::attempt($credentials)) {
    $user = Auth::user();
    
    var_dump($user); // Returns instance of App\User;
    
    var_dump($user->adldapUser); // Returns instance of Adldap\Models\User;
    
    // Retrieving the authenticated LDAP users groups
    $groups = $user->adldapUser->getGroups();
}
```

You **must** insert the trait `Adldap\Laravel\Traits\AdldapUserModelTrait` onto your configured auth User model, **OR**
Add the public property `adldapUser` to your model.

```php
<?php
// app/User.php

namespace App;

use Adldap\Laravel\Traits\AdldapUserModelTrait;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword, AdldapUserModelTrait; // Insert trait here

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
}
```

#### Login Fallback

> **Note**: This feature was introduced in `v1.3.9`. You'll will need to re-publish the Adldap Auth configuration file
to receive this option.

The login fallback option allows you to login as a local database user using the Eloquent authentication driver if 
active directory authentication fails. This option would be handy in environments where:
 
- You may have some active directory users and other users registering through the website itself (user does not exist in your AD).
- Local development where your AD server may be unavailable

To enable it, simply set the option to true in your `adldap_auth.php` configuration file:

```php
'login_fallback' => false, // Set to true.
```

#### Windows Authentication (SSO) Middleware

> **Note**: This feature was introduced in `v1.4.3`. You will need to re-publish the Adldap Auth configuration file
to receive this option.

> **Requirements**: This feature assumes that you have enabled `Windows Authentication` in IIS, or have enabled it
in some other means with Apache. Adldap does not set this up for you. To enable Windows Authentication, visit:
https://www.iis.net/configreference/system.webserver/security/authentication/windowsauthentication/providers/add

SSO authentication allows you to authenticate your users by the pre-populated `$_SERVER['AUTH_USER']` (or `$_SERVER['REMOTE_USER']`)
that is filled when users visit your site when SSO is enabled on your server. This is configurable in your `adldap_auth.php`
configuration file.

To use the middleware, insert it on your middleware stack:

```php
protected $middlewareGroups = [
    'web' => [
        Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        Middleware\VerifyCsrfToken::class,
        \Adldap\Laravel\Middleware\WindowsAuthenticate::class, // Inserted here.
    ],
];
```

Now when you visit your site, a user account will be created (if one doesn't exist already)
with a random 16 character string password and then automatically logged in. Neat huh?

#### Login Limitation Filter

> **Note**: This feature was introduced in `v1.4.6`. You will need to re-publish the Adldap Auth configuration file
to receive this option.

Inside of your `config/adldap_auth.php` configuration, you can now insert a raw LDAP filter to specify which users are allowed to authenticate.

This filter persists to the Windows Authentication Middleware as well.

For example, to allow only users to that contain an email address to login, insert the filter: `(mail=*)`:

```php
 /*
 |--------------------------------------------------------------------------
 | Limitation Filter
 |--------------------------------------------------------------------------
 |
 | The limitation filter allows you to enter a raw filter to only allow
 | specific users / groups / ous to authenticate.
 |
 | This should be a standard LDAP filter.
 |
 */

 'limitation_filter' => '(mail=*)',
```

For another example, here's how you can limit users logging in that are apart of a specific group:

> **Note**: This will also allow nested group users to login as well.

```php
'limitation_filter' => '(memberof:1.2.840.113556.1.4.1941:=CN=MyGroup,DC=example,DC=com)',
```

#### Multiple Authentication Connections

> **Note**: This feature was introduced in `v2.0.0`.

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

> **Note**: This feature was introduced in `v2.0.8`.
>
> You must delete and re-publish your Adldap2 Auth configuration
> for this option to be present.

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

#### Importing Users

> **Note**: This feature was introduced in `v2.0.13`.

You can now import all users manually by running the artisan command:

```cmd
php artisan adldap:import
```

The command requires that you have the Adldap auth driver setup and configured before running.

When users are imported, they are given a random 16 character hashed password to ensure they are secure upon import.

After running the import, you will receive information of how many users were imported:

```cmd
Found 370 user(s). Importing...
 370/370 [============================] 100%
Successfully imported / synchronized 251 user(s).
```

Tips:

 - Users who already exist inside your database will be updated with your configured `sync_attributes`
 - Users are never deleted from the import command, you will need to clear users regularly through your model
 - Successfully imported (new) users are reported in your log files:
  - `[2016-06-29 14:51:51] local.INFO: Imported user johndoe`
 - Unsuccessful imported users are also reported in your log files, with the message of the exception:
  - `[2016-06-29 14:51:51] local.ERROR: Unable to import user janedoe. SQLSTATE[23000]: Integrity constraint violation: 1048`
 - To run the import without logging, use `php artisan adldap:import --log=false`
 - To import a single user, insert their username: `php artisan adldap:import jdoe`
  - Specifying a username uses ambiguous naming resolution, so you're able to specify attributes other than their username, such as their email (`php artisan adldap:import jdoe@mail.com`).
  - If you have a password mutator (setter) on your User model, it will not override it. This way, you can hash the random 16 characters any way you please.

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

### Auth Providers

> **Note**: This feature in coming in `v3.0.0`.

Inside your `config/adldap_auth.php` file, there is an option named `provider`.

This option allows you to configure which type of authentication method you prefer with Adldap.

#### DatabaseUserProvider

For synchronizing LDAP users to your local applications database, use the provider:

```php
Adldap\Laravel\Auth\DatabaseUserProvider::class
```

Using the `DatabaseUserProvider` utilizes your configured Eloquent model.

#### NoDatabaseUserProvider

If you just require LDAP authentication, use the provider:

```php
Adldap\Laravel\Auth\NoDatabaseUserProvider::class
```

##### Things to Note about the NoDatabaseUserProvider

When calling `Auth::user()`, you will receive an instance of `Adldap\Models\User`. **Not**
an instance of an `App\User`. This means you will need to change laravel's
built in views for accessing the users name, and other information.

For a small example, inside Laravel's stock view `layouts/app.blade.php`, this will result in an error:

```
{{ Auth::user()->name }}
```

Since you're actually using an instance of `Adldap\Models\User`, you'll need to replace the above with:

```
{{ Auth::user()->getCommonName() }}
```

You can read more about the methods available on the `Adldap\Models\User` instance here: 

[Adldap2 - User Model](https://github.com/Adldap2/Adldap2/blob/master/docs/models/user.md)
