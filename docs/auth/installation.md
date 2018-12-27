# Installation

The installation process slightly differs depending on your  Laravel version. Please look at the install guide with the Laravel version you're using.

## Laravel 5.0 - 5.1

Insert the `AdldapAuthServiceProvider` into your `config/app.php` file:

```php
Adldap\Laravel\AdldapAuthServiceProvider::class,
```

Publish the auth configuration:

```bash
php artisan vendor:publish --provider="Adldap\Laravel\AdldapAuthServiceProvider"
```

Change the auth driver in `config/auth.php` to `ldap`:

```php
'driver' => 'ldap',
```

## Laravel 5.2 - 5.4

Insert the `AdldapAuthServiceProvider` into your `config/app.php` file:

```php
Adldap\Laravel\AdldapAuthServiceProvider::class,
```

Publish the auth configuration file:

```bash
php artisan vendor:publish --provider="Adldap\Laravel\AdldapAuthServiceProvider"
```

Open your `config/auth.php` configuration file and change the following:

Change the `driver` value inside the `users` authentication provider to `ldap`:

```php
'providers' => [
    'users' => [
        'driver' => 'ldap', // Changed from 'eloquent'
        'model' => App\User::class,
    ],
],
```

## Laravel 5.5

When using Laravel 5.5, the service providers are registered automatically,
however you will still need to publish the configuration file using the
command below:

```bash
php artisan vendor:publish --provider="Adldap\Laravel\AdldapAuthServiceProvider"
```

Then, open your `config/auth.php` configuration file and change the `driver`
value inside the `users` authentication provider to `ldap`:

```php
'providers' => [
    'users' => [
        'driver' => 'ldap', // Changed from 'eloquent'
        'model' => App\User::class,
    ],
],
```

> **Tip**: Now that you've enabled LDAP authentication, you may want to turn off some of
> Laravel's authorization routes such as password resets, registration, and email
> verification.
>
> You can do so in your `routes/web.php` file via:
> 
> ```php
> Auth::routes([
>    'reset' => false,
>    'verify' => false,
>    'register' => false,
> ]);
> ```

Now that you've completed the basic installation, let's move along to the [setup guide](setup.md).
