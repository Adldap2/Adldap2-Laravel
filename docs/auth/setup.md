# Setup

- [Connection](#connection)
- [Provider](#provider)
- [Rules](#rules)
- [Scopes](#scopes)
- [Usernames](#usernames)
- [Passwords](#passwords)
- [Login Fallback](#login-fallback)
- [Synchronizing](#synchronizing)

## Connection

The `connection` option is the name of the LDAP connection to use for authentication that you've configured in your `ldap.php` file.

## Provider

Authentication providers allow you to choose how LDAP users are authenticated into your application.

There are two built in providers. Please view their documentation to see which one is right for you.

* [DatabaseUserProvider](#databaseuserprovider)
* [NoDatabaseUserProvider](#nodatabaseuserprovider)

### DatabaseUserProvider

The `DatabaseUserProvider` allows you to synchronize LDAP users to your applications database.

To use it, insert it in your `config/ldap_auth.php` in the `provider` option:

```php
'provider' => Adldap\Laravel\Auth\DatabaseUserProvider::class
```

Using this provider utilizes your configured Eloquent model in `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'ldap',
        'model' => App\User::class,
    ],
],
```

When you've authenticated successfully, use the method `Auth::user()` as you would normally to retrieve the currently authenticated user:

```php
// Instance of \App\User.
$user = Auth::user();

echo $user->email;
```

### NoDatabaseUserProvider

The `NoDatabaseUserProvider` allows you to authenticate LDAP users without synchronizing them.

This provider is great for smaller applications that don't require you to attach data to authenticating users and is great for simply authorizing access.

#### Important Note About Session Drivers

When using the `database` session driver with the `NoDatabaseUserProvider`, you **must**
change the `user_id` data type in the generated Laravel sessions migration (`database/migrations/2018_05_03_182019_create_sessions_table.php`)
to `varchar`. This is because the identifier for LDAP records is
a GUID - which contains letters and dashes (incompatible with
the `integer` type of databases).

#### Important Note About Default Views

Due to Laravel's generated blade views with the `auth:make` command, any
views that utilize Eloquent User model attributes will need to be
re-written for compatibility with this provider.

For example, in the generated `resources/views/layouts/app.blade.php`, you will
need to rewrite `Auth::user()->name` to `Auth::user()->getCommonName();`

This is because the authenticated user will not be a standard Eloquent
model, it will be a `Adldap\Models\User` instance.

You will receive exceptions otherwise.

---

To use it, insert it in your `config/ldap_auth.php` in the `provider` option:

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

## Rules

Authentication rules allow you to restrict which LDAP users are able to authenticate, much like [scopes](#scopes),
but with the ability to perform checks on the specific user authenticating, rather than a global scope.

### Creating a Rule

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

### Implementing the Rule

To implement your new rule, you just need to insert it into your `config/ldap_auth.php` file:

```php
'rules' => [
    
    App\Rules\OnlyManagersAndAccounting::class,

],
```

Now when you try to login, the LDAP user you login with will need to be apart of either the `Accounting` or `Managers` Organizational Unit.

### Example Rules

#### Group Validation

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

## Scopes

Scopes allow you to restrict which LDAP users are allowed to login to your application.

If you're familiar with Laravel's [Query Scopes](https://laravel.com/docs/5.7/eloquent#query-scopes),
then these will feel very similar.

### Creating a Scope

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

### Implementing a Scope

Now that we've created our scope (`app/Scopes/AccountingScope.php`), we can insert it into our `config/ldap_auth.php` file:

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

## Usernames

Inside your `config/ldap_auth.php` file there is a configuration option named `usernames`:

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

Let's go through each option with their meaning.

### LDAP

The LDAP array contains two elements each with a key and value.

The `discover` key contains the LDAP users attribute you would like your authenticating users to be located by.

> **Note**: If you're using the `NoDatabaseUserProvider` it is extremely important to know that this value is used as the key to retrieve the inputted username from the `Auth::attempt()` credentials array.
>
> For example, if you're executing an `Auth::attempt(['username' => 'jdoe..'])` and you have a `discover` value set to `userprincipalname` then the Adldap2-Laravel auth driver will try to retrieve your users username from the given credentials array with the key `userprincipalname`. This would generate an exception since this key does not exist in the above credentials array.

For example, executing the following:

```php
Auth::attempt(['email' => 'jdoe@corp.com', 'password' => 'password'])
```

Will perform an LDAP search for a user with the `userprincipalname` equal to `jdoe@corp.com`.

If you change `Auth::attempt()` `email` key, you will need to change the `eloquent` key to match.

The `authenticate` key contains the LDAP users attriubte you would like to perform LDAP authentication on.

For example, executing the following:

```php
Auth::attempt(['email' => 'jdoe@corp.com', 'password' => 'password'])
```

Will try to locate a user in your LDAP directory with a `userprincipalname` equal to `jdoe@corp.com`. Then, when an LDAP record of this user is located, their `disintinguishedname` will be retrieved from this record, an be passed into an `Adldap\Auth\Guard::attempt()` (ex `Guard::attempt('cn=John Doe,ou=Users,dc=corp,dc=com', 'password')`).

> **Note**: It's **extremely** important to know that your configured `account_suffix` and `account_prefix` (located in your `config/ldap.php` file) will be appended or prepended *onto* this passed in username.

You can ignore the `windows` configuration array, unless you're planning on using the included [middleware](auth/middleware.md) for single sign on authentication.

### Eloquent

The eloquent key contains a value that should match the username column you have set up in your `users` database table.

For example, if you're using a `username` field instead of `email` in your application, you will need to change this option to `username`.

> **Note**: If you're using the `DatabaseUserProvider` it is extremely important to know that this value is used as the key to retrieve the inputted username from the `Auth::attempt()` credentials array.
>
> For example, if you're executing an `Auth::attempt(['username' => 'jdoe..'])` and you have an `eloquent` value set to `email` then the Adldap2-Laravel auth driver will try to retrieve your users username from the given credentials array with the key `email`. This would generate an exception since this key does not exist in the above credentials array.

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

**Be sure** to update the `sync_attributes` option to synchronize the users `username` as well as this is not done automatically. You will receive a SQL exception otherwise.

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

## Passwords

### Sync

The password sync option allows you to automatically synchronize users LDAP passwords to your local database. These passwords are hashed natively by Laravel using the Hash::make() method.

Enabling this option would also allow users to login to their accounts using the password last used when an LDAP connection was present.

If this option is disabled, the local database account is applied a random 16 character hashed password upon first login, and will lose access to this account upon loss of LDAP connectivity.

This option must be true or false and is only applicable
to the `DatabaseUserProvider`.

### Column

The column option allows you to change the database column that contains the users password.

Change this if your database column is different than `password` and you have enabled the above sync option.

## Login Fallback

The login fallback option allows you to login as a local database user using the default Eloquent authentication
driver if LDAP authentication fails. This option would be handy in environments where:

- You may have some active directory users and other users registering through
  the website itself (user does not exist in your LDAP directory).
- Local development where your LDAP server may be unavailable

To enable it, simply set the option to true in your `config/ldap_auth.php` configuration file:

```php
'login_fallback' => env('LDAP_LOGIN_FALLBACK', true), // Set to true.
```

## Synchronizing

Inside your `config/ldap_auth.php` file there is a configuration option named `sync_attributes`. This
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
to the column not existing on your users database table.

### Attribute Handlers

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

## Logging

The `logging` array contains a list of the events to be logged when certain [events](events.md) occur using Adldap2-Laravel.

Each element in the array consists of the key (the occurring event) and the value (the listener that performs the logging of said event).

You can remove any or all of these if you'd prefer nothing to be logged for the event. No passwords are logged with any of the events.
