# Authentication Providers

* [DatabaseUserProvider](#DatabaseUserProvider)
* [NoDatabaseUserProvider](#NoDatabaseUserProvider)

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

## NoDatabaseUserProvider

The `NoDatabaseUserProvider` allows you to authenticate LDAP users without synchronizing them.

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