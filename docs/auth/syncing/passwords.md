# Password Synchronization

The password sync option allows you to automatically synchronize
users LDAP passwords to your local database. These passwords are
hashed natively by laravel.

Enabling this option would also allow users to login to their
accounts using the password last used when an LDAP connection
was present.

If this option is disabled, the local user account is applied
a random 16 character hashed password, and will lose access
to this account upon loss of LDAP connectivity.

This feature is disabled by default.

```php
'password_sync' => env('ADLDAP_PASSWORD_SYNC', false),
```