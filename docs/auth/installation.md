# Installation

To start configuring the authentication driver, you will need
to publish the configuration file using the command below:

```bash
php artisan vendor:publish --provider "Adldap\Laravel\AdldapAuthServiceProvider"
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

Now that you've completed the basic installation, let's move along to the [setup guide](auth/setup.md).
