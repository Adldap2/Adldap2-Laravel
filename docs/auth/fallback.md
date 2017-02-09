# Login Fallback

The login fallback option allows you to login as a local database user using the Eloquent authentication driver if 
active directory authentication fails. This option would be handy in environments where:
 
- You may have some active directory users and other users registering through the website itself (user does not exist in your AD).
- Local development where your AD server may be unavailable

To enable it, simply set the option to true in your `config/adldap_auth.php` configuration file:

```php
'login_fallback' => false, // Set to true.
```

## Developing Locally without an AD connection

You can continue to develop and login to your application without a connection to your AD server in the following scenario:

* You have `auto_connect` set to `false` in your `adldap.php` configuration
 > This is necessary so we don't automatically try and bind to your AD server when your application boots.

* You have `login_fallback` set to `true` in your `adldap_auth.php` configuration
 > This is necessary so we fallback to the standard `eloquent` auth driver.

* You have `password_sync` set to `true` in your `adldap_auth.php` configuration
 > This is necessary so we can login to the account with the last password that was used when an AD connection was present.

* You have logged into the synchronized LDAP account previously
 > This is necessary so the account actually exists in your local app's database.

If you have this configuration, you will have no issues developing an
application without a persistent connection to your LDAP server.
