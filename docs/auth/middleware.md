# Single Sign On (SSO) Middleware

> **Requirements**: This feature assumes that you have enabled `Windows Authentication` in IIS, or have enabled it
in some other means with Apache. Adldap2 does not set this up for you. To enable Windows Authentication, visit:
https://www.iis.net/configreference/system.webserver/security/authentication/windowsauthentication/providers/add

SSO authentication allows you to authenticate your domain users automatically in your application by
the pre-populated `$_SERVER['AUTH_USER']` (or `$_SERVER['REMOTE_USER']`) that is filled when
users visit your site when SSO is enabled on your server. This is
configurable in your `adldap_auth.php`configuration file.

> **Note**: The WindowsAuthenticate middleware utilizes the `scopes` inside your `config/adldap.php` file.
> A user may successfully authenticate against your AD server when visiting your site, but
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

## Configuration

You can configure the attributes users are logged in by in your configuration:

```php
'windows_auth_attribute' => ['samaccountname' => 'AUTH_USER'],
```

If a user is logged into a domain joined computer and is visiting your website with windows
authentication enabled, IIS will set the PHP server variable `AUTH_USER`. This variable
is usually equal to the currently logged in users `samaccountname`.

The configuration array represents this mapping. The WindowsAuthenticate middleware will
check if the server variable is set, and try to locate the user in your AD server
by their `samaccountname`.
