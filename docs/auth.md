# Auth Driver

The Adldap2 Laravel auth driver allows you to seamlessly authenticate LDAP users,
as well as have a local database record of the user.

This allows you to easily attach information to the users account
as you would a regular laravel application.

## Installation

### Laravel 5.1

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

### Laravel 5.2 - 5.4

Insert the `AdldapAuthServiceProvider` into your `config/app.php` file:

```php
Adldap\Laravel\AdldapAuthServiceProvider::class,
```

Publish the auth configuration file:

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

### Laravel 5.5

When using Laravel 5.5, the service providers are registered automatically,
however you will still need to publish the configuration files using the
command below:

```bash
php artisan vendor:publish --tag="adldap"
```

Then, open your `config/auth.php` configuration file and change the `driver`
value inside the `users` authentication provider to `adldap`:

```php
'providers' => [
    'users' => [
        'driver' => 'adldap', // Changed from 'eloquent'
        'model' => App\User::class,
    ],
],
```

## Basic Setup

### Usernames

Inside your `config/adldap_auth.php` file there is a configuration option named `usernames`.

This array contains the `ldap` attribute you use for authenticating users, as well
as the `eloquent` attribute for locating the LDAP users local model.

```php
'usernames' => [

    'ldap' => [
        
        'discover' => 'userprincipalname',
        
        'authenticate' => 'distinguishedname',
    
    ],
    
    'eloquent' => 'email',
    
    'windows' => [
        'discover' => 'samaccountname',
        
        'key' => 'AUTH_USER',
    ],

],
```

If you're using a `username` field instead of `email` in your application, you will need to change this configuration.

> **Note**: Keep in mind you will also need to update your `database/migrations/2014_10_12_000000_create_users_table.php`
> migration to use a username field instead of email, **as well as** your LoginController.

For example, if you'd like to login users by their `samaccountname`:

```php
'usernames' => [

    'ldap' => [
        
        'discover' => 'samaccountname', // Changed from `userprincipalname`
        
        'authenticate' => 'distinguishedname',
    
    ],
    
    'eloquent' => 'username', // Changed from `email`

],
```

**Be sure** to update the `sync_attributes` option to synchronize the users
`username` as well. Otherwise, you will receive a SQL exception.

```php
'sync_attributes' => [

    'username' => 'samaccountname',
    'name' => 'cn',

],
```

### Logging In

Login a user regularly using `Auth::attempt($credentials);`.

Once a user is authenticated, retrieve them as you would regularly:

> **Note**: The below code is just an example. You should not need to modify
> the `login()` method on the default `LoginController`, unless
> you require unique functionality.

```php
public function login(Request $request)
{
    if (Auth::attempt($request->only(['email', 'password']))) {
        
        // Returns \App\User model configured in `config/auth.php`.
        $user = Auth::user();
        
        return redirect()->to('home')
            ->withMessage('Logged in!');
    }
    
    return redirect()->to('login')
        ->withMessage('Hmm... Your username or password is incorrect');
}
```

## Upgrading From 3.* to 4.*

**Estimated Upgrade Time: 1 hour**

With `v4.0`, there are some significant changes to the code base.

This new version utilizes the newest `v8.0` release of the underlying Adldap2 repository.

Please visit the [Adldap2](https://github.com/Adldap2/Adldap2/releases/tag/v8.0.0)
repository for the release notes and changes.

However for this package you should only have to change your `adldap_auth.php` configuration.

### Authentication Driver

LDAP connection exceptions are now caught when authentication attempts occur.

These exceptions are logged to your configured logging driver so you can view the stack trace and discover issues easier.

### Configuration

1. Delete your `config/adldap_auth.php`
2. Run `php artisan vendor:publish --tag="adldap"`
3. Reconfigure auth driver in `config/adldap_auth.php`

#### Usernames Array

The `usernames` array has been updated with more options.

You can now configure the attribute you utilize for discovering the LDAP user as well as authenticating.

This will help users who use OpenLDAP and other distributions of directory servers.

Each configuration option is extensively documented in the published
file, so please take a moment to review it once published.

This array now also contains the `windows_auth_attribute` array (shown below).

```php
// v3.0
'usernames' => [

    'ldap' => 'userprincipalname',

    'eloquent' => 'email',
    
],

// v4.0
'usernames' => [

     'ldap' => [
     
        'discover' => 'userprincipalname',
        
        'authenticate' => 'distinguishedname',
        
    ],
    
    'eloquent' => 'email',
    
    'windows' => [
    
        'discover' => 'samaccountname',
        
        'key' => 'AUTH_USER',
        
    ],
    
],
```

#### Logging

Logging has been added for authentication requests to your server.

Which events are logged can be configured in your `adldap_auth.php` file.

Here's an example of the information logged:

```
[2017-11-14 22:19:45] local.INFO: User 'Steve Bauman' has been successfully found for authentication.  
[2017-11-14 22:19:45] local.INFO: User 'Steve Bauman' is being imported.  
[2017-11-14 22:19:45] local.INFO: User 'Steve Bauman' is being synchronized.  
[2017-11-14 22:19:45] local.INFO: User 'Steve Bauman' has been successfully synchronized.  
[2017-11-14 22:19:45] local.INFO: User 'Steve Bauman' is authenticating with username: 'sbauman@company.org'  
[2017-11-14 22:19:45] local.INFO: User 'Steve Bauman' has successfully passed LDAP authentication.  
[2017-11-14 22:19:46] local.INFO: User 'Steve Bauman' has been successfully logged in.  
```

#### Resolver

The resolver configuration option has now been removed.

It has been modified to utilize Laravel's Facades so you can now swap the implementation at runtime if you wish.

The complete namespace for this facade is below:

```
Adldap\Laravel\Facades\Resolver
```

Usage:

```php
use Adldap\Laravel\Facades\Resolver;

Resolver::swap(new MyResolver());
```

#### Importer

The importer configuration option has now been removed.

The importer command is bound to Laravel's IoC and can be swapped out with your own implementation if you wish.

### NoDatabaseUserProvider

The `NoDatabaseUserProvider` will now locate users by their ObjectGUID instead of their ObjectSID.

## Features

### Providers

#### Authentication Providers

Authentication providers allow you to choose how LDAP users are authenticated into your application.

There are two built in providers. Please view their documentation to see which one is right for you.

* [DatabaseUserProvider](#databaseuserprovider)
* [NoDatabaseUserProvider](#nodatabaseuserprovider-1)

##### DatabaseUserProvider

The `DatabaseUserProvider` allows you to synchronize LDAP users to your applications database.

To use it, insert it in your `config/adldap_auth.php` in the `provider` option:

```php
'provider' => Adldap\Laravel\Auth\DatabaseUserProvider::class
```
    
Using this provider utilizes your configured Eloquent model in `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'adldap',
        'model' => App\User::class,
    ],
],
```

When you've authenticated successfully, use the method `Auth::user()` as you would
normally to retrieve the currently authenticated user:

```php
// Instance of \App\User.
$user = Auth::user();

echo $user->email;
```

##### NoDatabaseUserProvider

The `NoDatabaseUserProvider` allows you to authenticate LDAP users without synchronizing them.

###### Important Note About Session Drivers

When using the `database` session driver with the `NoDatabaseUserProvider`, you **must**
change the `user_id` data type in the generated Laravel sessions migration (`database/migrations/2018_05_03_182019_create_sessions_table.php`)
to `varchar`. This is because the identifier for LDAP records is
a GUID - which contains letters and dashes (incompatible with
the `integer` type of databases).

###### Important Note About Default Views

Due to Laravel's generated blade views with the `auth:make` command, any
views that utilize Eloquent User model attributes will need to be
re-written for compatibility with this provider.

For example, in the generated `resources/views/layouts/app.blade.php`, you will
need to rewrite `Auth::user()->name` to `Auth::user()->getCommonName();`

This is because the authenticated user will not be a standard Eloquent
model, it will be a `Adldap\Models\User` instance.

You will receive exceptions otherwise.

---

To use it, insert it in your `config/adldap_auth.php` in the `provider` option:

```php
'provider' => Adldap\Laravel\Auth\NoDatabaseUserProvider::class
```

Inside your `config/auth.php` file, you can remove the `model` key in your provider array since it won't be used:

```php
'providers' => [
    'users' => [
        'driver' => 'adldap',
    ],
],
```

When you've authenticated successfully, use the method `Auth::user()` as you would
normally to retrieve the currently authenticated user:

```php
// Instance of \Adldap\Models\User.
$user = Auth::user();

echo $user->getCommonName();

echo $user->getAccountName();
```

### Scopes

Scopes allow you to restrict which LDAP users are allowed to login to your application.

If you're familiar with Laravel's [Query Scopes](https://laravel.com/docs/5.4/eloquent#query-scopes),
then these will feel very similar.

#### Creating a Scope

To create a scope, it must implement the interface `Adldap\Laravel\Scopes\ScopeInterface`.

For this example, we'll create a folder inside our `app` directory containing our scope named: `Scopes`.

Of course, you can place these scopes wherever you desire, but in this example, our final scope path will be:

```
../my-application/app/Scopes/AccountingScope.php
```

With this scope, we want to only allow members of an Active Directory group named: `Accounting`:

```php
namespace App\Scopes;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

class AccountingScope implements ScopeInterface
{
    /**
     * Apply the scope to a given LDAP query builder.
     *
     * @param Builder $query
     *
     * @return void
     */
    public function apply(Builder $query)
    {
        // The distinguished name of our LDAP group.
        $accounting = 'cn=Accounting,ou=Groups,dc=acme,dc=org';
        
        $query->whereMemberOf($accounting);
    }
}
```

#### Implementing a Scope

Now that we've created our scope (`app/Scopes/AccountingScope.php`), we can insert it into our `config/adldap_auth.php` file:

```php
'scopes' => [

    // Only allows users with a user principal name to authenticate.

    Adldap\Laravel\Scopes\UpnScope::class,
    
    // Only allow members of 'Accounting' to login.
    
    App\Scopes\AccountingScope::class,

],
```

Once you've inserted your scope into the configuration file, you will now only be able
to authenticate with users that are a member of the `Accounting` group.

All other users will be denied authentication, even if their credentials are valid.

> **Note**: If you're caching your configuration files, make sure you
> run `php artisan config:clear` to be able to use your new scope.

### Rules

Authentication rules allow you to restrict which LDAP users are able to authenticate, much like [scopes](#scopes),
but with the ability to perform checks on the specific user authenticating.

#### Creating a Rule

To create a rule, it must extend the class `Adldap\Laravel\Validation\Rules\Rule`.

Two properties will be available to you inside the rule. A `$user` property that
contains the LDAP user model, as well as their Eloquent `$model`

> **Note**: If you utilize the `NoDatabaseUserProvider` instead of the default
> `DatabaseUserProvider`, then only the `$user` property will be available.

We'll create a folder in our `app` directory containing our rule named `Rules`.

With this example rule, we only want to allow users to login if they are inside specific OU's:

```php
namespace App\Rules;

use Adldap\Laravel\Validation\Rules\Rule;

class OnlyManagersAndAccounting extends Rule
{
    /**
     * Determines if the user is allowed to authenticate.
     *
     * @return bool
     */   
    public function isValid()
    {
        $ous = [
            'ou=Accounting,dc=acme,dc=org',
            'ou=Managers,dc=acme,dc=org',
        ];
    
        return str_contains($this->user->getDn(), $ous);
    }
}
```

#### Implementing the Rule

To implement your new rule, you just need to insert it into your `config/adldap_auth.php` file:

```php
'rules' => [
    
    App\Rules\OnlyManagersAndAccounting::class,

],
```

Now when you try to login, the LDAP user you login with will need to be
apart of either the `Accounting` or `Managers` Organizational Unit.

#### Example Rules

##### Group Validation

To validate that an authenticating user is apart of one or more LDAP groups, we can perform this with a `Rule`:

```php
namespace App\Rules;

use Adldap\Models\User as LdapUser;
use Adldap\Laravel\Validation\Rules\Rule;

class IsAccountant extends Rule
{
    /**
     * Determines if the user is allowed to authenticate.
     *
     * Only allows users in the `Accounting` group to authenticate.
     *
     * @return bool
     */   
    public function isValid()
    {
        return $this->user->inGroup('Accounting');
    }
}
```

Once you've implemented the above rule, only LDAP users that are apart of the `Accounting` group, will be allowed to authenticate.

### Syncing Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `sync_attributes`. This
is an array of attributes where the key is the eloquent `User` model attribute, and the
value is the active directory users attribute:

```php
'sync_attributes' => [

    'email' => 'userprincipalname',

    'name' => 'cn',
],
```

By default, the `User` models `email` and `name` attributes are synchronized to
the LDAP users `userprincipalname` and `cn` attributes.

This means, upon login, the users `email` and `name` attribute on Laravel `User` Model will be set to the
LDAP users `userprincipalname` and common name (`cn`) attribute, **then saved**.

Feel free to add more attributes here, however be sure that your `users` database table contains
the key you've entered, otherwise you will receive a SQL exception upon authentication, due
to the column not existing on your users datable table.

#### Attribute Handlers

If you're looking to synchronize an attribute from an Adldap2 model that contains an array or an
object, or sync attributes yourself, you can use an attribute handler class
to sync your model attributes manually. For example:

> **Note**: The class must contain a `handle()` method. Otherwise you will receive an exception.

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

#### Password Synchronization

The password sync option allows you to automatically synchronize
users LDAP passwords to your local database. These passwords are
hashed natively by laravel.

Enabling this option would also allow users to login to their
accounts using the password last used when an LDAP connection
was present.

If this option is disabled, the local user account is applied
a random 16 character hashed password, and will lose access
to this account upon loss of LDAP connectivity.

This feature is disabled by default.

```php
'password_sync' => env('ADLDAP_PASSWORD_SYNC', false),
```

### Events

Adldap2-Laravel raises a variety of events throughout authentication attempts.

You may attach listeners to these events in your `EventServiceProvider`:

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [

    'Adldap\Laravel\Events\Authenticating' => [
        'App\Listeners\LogAuthenticating',
    ],

    'Adldap\Laravel\Events\Authenticated' => [
        'App\Listeners\LogLdapAuthSuccessful',
    ],
    
    'Adldap\Laravel\Events\AuthenticationSuccessful' => [
        'App\Listeners\LogAuthSuccessful'
    ],
    
    'Adldap\Laravel\Events\AuthenticationFailed' => [
        'App\Listeners\LogAuthFailure',
    ],
    
    'Adldap\Laravel\Events\AuthenticationRejected' => [
        'App\Listeners\LogAuthRejected',
    ],
    
    'Adldap\Laravel\Events\AuthenticatedModelTrashed' => [
        'App\Listeners\LogUserModelIsTrashed',
    ],
    
    'Adldap\Laravel\Events\AuthenticatedWithCredentials' => [
         'App\Listeners\LogAuthWithCredentials',
    ],
    
    'Adldap\Laravel\Events\AuthenticatedWithWindows' => [
        'App\Listeners\LogSSOAuth',
    ],
    
    'Adldap\Laravel\Events\DiscoveredWithCredentials' => [
         'App\Listeners\LogAuthUserLocated',
    ],
    
    'Adldap\Laravel\Events\Importing' => [
        'App\Listeners\LogImportingUser',
    ],
    
    'Adldap\Laravel\Events\Synchronized' => [
         'App\Listeners\LogSynchronizedUser',
    ],
    
    'Adldap\Laravel\Events\Synchronizing' => [
        'App\Listeners\LogSynchronizingUser',
    ],

];
```

> **Note:** For some real examples, you can browse the listeners located
> in: `vendor/adldap2/adldap2-laravel/src/Listeners` and see their usage.

### Fallback

The login fallback option allows you to login as a local database user using the default Eloquent authentication
driver if LDAP authentication fails. This option would be handy in environments where:
 
- You may have some active directory users and other users registering through
  the website itself (user does not exist in your LDAP directory).
- Local development where your LDAP server may be unavailable

To enable it, simply set the option to true in your `config/adldap_auth.php` configuration file:

```php
'login_fallback' => env('ADLDAP_LOGIN_FALLBACK', true), // Set to true.
```

#### Developing Locally without an LDAP connection

You can continue to develop and login to your application without a
connection to your LDAP server in the following scenario:

* You have `auto_connect` set to `false` in your `adldap.php` configuration
 > This is necessary so we don't automatically try and bind to your LDAP server when your application boots.

* You have `login_fallback` set to `true` in your `adldap_auth.php` configuration
 > This is necessary so we fallback to the standard `eloquent` auth driver.

* You have `password_sync` set to `true` in your `adldap_auth.php` configuration
 > This is necessary so we can login to the account with the last password that was used when an LDAP connection was present.

* You have logged into the synchronized LDAP account previously
 > This is necessary so the account actually exists in your local app's database.

If you have this configuration, you will have no issues developing an
application without a persistent connection to your LDAP server.

### Model Binding

Model binding allows you to attach the users LDAP model to their Eloquent
model so their LDAP data is available on every request automatically.

> **Note**: Before we begin, enabling this option will perform a single query on your LDAP server for a logged
in user **per request**. Eloquent already does this for authentication, however
this could lead to slightly longer load times (depending on your LDAP
server and network speed of course).

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

Now, after you've authenticated a user (with the `adldap` auth driver),
their LDAP model will be available on their `User` model:

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

## Middleware

### Single Sign On (SSO) Middleware

SSO authentication allows you to authenticate your domain users automatically in your application by
the pre-populated `$_SERVER['AUTH_USER']` (or `$_SERVER['REMOTE_USER']`) that is filled when
users visit your site when SSO is enabled on your server. This is
configurable in your `adldap_auth.php`configuration file.

> **Requirements**: This feature assumes that you have enabled `Windows Authentication` in IIS, or have enabled it
in some other means with Apache. Adldap2 does not set this up for you. To enable Windows Authentication, visit:
https://www.iis.net/configreference/system.webserver/security/authentication/windowsauthentication/providers/add

> **Note**: The WindowsAuthenticate middleware utilizes the `scopes` inside your `config/adldap.php` file.
> A user may successfully authenticate against your LDAP server when visiting your site, but
> depending on your scopes, may not be imported or logged in.

To use the middleware, insert it on your middleware stack inside your `app/Http/Kernel.php` file:

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

Now when you visit your site, a user account will be created (if one does not exist already)
with a random 16 character string password and then automatically logged in. Neat huh?

### Configuration

You can configure the attributes users are logged in by in your configuration:

```php
'usernames' => [

    //..//

    'windows' => [
    
        'discover' => 'samaccountname',
    
        'key' => 'AUTH_USER',
    
    ],

],
```

If a user is logged into a domain joined computer and is visiting your website with windows
authentication enabled, IIS will set the PHP server variable `AUTH_USER`. This variable
is usually equal to the currently logged in users `samaccountname`.

The configuration array represents this mapping. The WindowsAuthenticate middleware will
check if the server variable is set, and try to locate the user in your LDAP server
by their `samaccountname`.
