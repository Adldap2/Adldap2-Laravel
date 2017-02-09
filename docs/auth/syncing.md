# Syncing Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `sync_attributes`. This
is an array of attributes where the key is the eloquent `User` model attribute, and the
value is the active directory users attribute.

By default, the `User` models `email` and `name` attributes are synchronized to
the LDAP users `userprincipalname` and `cn` attributes.

This means, upon login, the users `email` and `name` attribute on Laravel `User` Model will be set to the
LDAP users `userprincipalname` and common name (`cn`) attribute, **then saved**.

Feel free to add more attributes here, however be sure that your `users` database table
contains the key you've entered, otherwise you will receive a SQL exception.

## Sync Attribute Handlers

If you're looking to synchronize an attribute from an Adldap2 model that contains an array or an
object, or sync attributes yourself, you can use an attribute handler class
to sync your model attributes manually. For example:

> **Note**: The class must contain a `handle()` method. Otherwise you will receive an exception.

> **Tip**: Attribute handlers are constructed using the `app()` helper. This means you can type-hint any application
> dependencies you may need in the handlers constructor.

```php
'sync_attributes' => [
    
    App\Handlers\LdapAttributeHandler::class,

],
```

The `LdapAttributeHandler`:

```php
namespace App\Handlers;

use App\User as EloquentUser;
use Adldap\Models\User as LdapUser;

class LdapAttributeHandler
{
    /**
     * Synchronizes ldap attributes to the specified model.
     *
     * @param LdapUser     $ldapUser
     * @param EloquentUser $eloquentUser
     *
     * @return void
     */
    public function handle(LdapUser $ldapUser, EloquentUser $eloquentUser)
    {
        $eloquentUser->name = $ldapUser->getCommonName();
    }
}
```

## Password Synchronization

The password sync option allows you to automatically synchronize
users AD passwords to your local database. These passwords are
hashed natively by laravel.

Enabling this option would also allow users to login to their
accounts using the password last used when an AD connection
was present.

If this option is disabled, the local user account is applied
a random 16 character hashed password, and will lose access
to this account upon loss of AD connectivity.

This feature is enabled by default.

```php
'password_sync' => env('ADLDAP_PASSWORD_SYNC', true),
```
