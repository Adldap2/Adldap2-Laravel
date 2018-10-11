# Authentication Providers

Authentication providers allow you to choose how LDAP users are authenticated into your application.

There are two built in providers. Please view their documentation to see which one is right for you.

* [DatabaseUserProvider](#databaseuserprovider)
* [NoDatabaseUserProvider](#nodatabaseuserprovider-1)

## DatabaseUserProvider

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

## NoDatabaseUserProvider

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