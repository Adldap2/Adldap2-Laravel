# Using Multiple LDAP Connections

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

Or, if you'd like to authenticate against all of your configured LDAP connections:

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
