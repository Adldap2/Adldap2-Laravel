# Upgrade Guide

## Upgrading from v4.* to v5.*

**Estimated Upgrade Time: 30 minutes**

Functionally, you should not need to change the way you use Adldap2-Laravel. There have been no major API changes that will impact your current usage.

However, there have been API changes to the core [Adldap2](https://github.com/Adldap2/Adldap2/releases/tag/v9.0.0) package.
It is heavily recommended to read the release notes to see if you may be impacted.

### Requirements

Adldap2-Laravel's PHP requirements has been changed. It now requires a minimum of PHP 7.1.

However, Adldap2's Laravel requirements **have not** changed. You can still use all versions of Laravel 5.

### Configuration

Both Adldap2's configuration files have been renamed to `ldap.php` and `ldap_auth.php` for simplicity.

Simply rename `adldap.php` to `ldap.php` and `adldap_auth.php` to `ldap_auth.php`.

If you'd prefer to re-publish them from scratch, here's a quick guide:

1. Delete your `config/adldap.php` file
2. Run `php artisan vendor:publish --provider="Adldap\Laravel\AdldapServiceProvider"`

If you're using the Adldap2 authentication driver, repeat the same steps for its configuration:

1. Delete your `config/adldap_auth.php` file
2. Run `php artisan vendor:publish --provider="Adldap\Laravel\AdldapAuthServiceProvider"`

#### Prefix and Suffix Changes

The configuration options `admin_account_prefix` and `admin_account_suffix` have been removed. Simply
apply a prefix and suffix to the username of the administrator account in your configuration.

The `account_prefix` and `account_suffix` options now only apply to user accounts that are
authenticated, not your configured administrator account.

This means you will need to add your suffix or prefix onto your configured administrators username if you require it.

#### Connection Settings

The configuration option named `connection_settings` inside each of your configured connections in the `adldap.php` (now `ldap.php`) configuration file has been renamed to `settings` for simplicity.

### Authentication Driver

The authentication driver name has been *renamed* to **ldap** instead of **adldap**. This is for the sake of simplicity.

Open your `auth.php` file and rename your authentication driver to `ldap`:

```php
'users' => [
    'driver' => 'ldap', // Renamed from 'adldap'
    'model' => App\User::class,
],
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

#### NoDatabaseUserProvider

The `NoDatabaseUserProvider` will now locate users by their ObjectGUID instead of their ObjectSID.
