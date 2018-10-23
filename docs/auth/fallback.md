# Fallback

The login fallback option allows you to login as a local database user using the default Eloquent authentication
driver if LDAP authentication fails. This option would be handy in environments where:

- You may have some active directory users and other users registering through
  the website itself (user does not exist in your LDAP directory).
- Local development where your LDAP server may be unavailable

To enable it, simply set the option to true in your `config/ldap_auth.php` configuration file:

```php
'login_fallback' => env('LDAP_LOGIN_FALLBACK', true), // Set to true.
```