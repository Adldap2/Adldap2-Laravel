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

### Laravel 5.2 & Up

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


## Basic Setup

### Usernames

Inside your `config/adldap_auth.php` file there is a configuration option named `usernames`.

This array contains the `ldap` attribute you use for authenticating users, as well
as the `eloquent` attribute for locating the LDAP users local model.

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

    'username' => 'samaccountname',
    'name' => 'cn',

],
```

### Logging In

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
