# Setup

## Usernames

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

## Logging In

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