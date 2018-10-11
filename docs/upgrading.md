# Upgrade Guide

## Upgrading from v4.* to v5.*



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