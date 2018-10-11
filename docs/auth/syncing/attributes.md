# Syncing Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `sync_attributes`. This
is an array of attributes where the key is the eloquent `User` model attribute, and the
value is the active directory users attribute:

```php
'sync_attributes' => [

    'email' => 'userprincipalname',

    'name' => 'cn',
],
```

By default, the `User` models `email` and `name` attributes are synchronized to
the LDAP users `userprincipalname` and `cn` attributes.

This means, upon login, the users `email` and `name` attribute on Laravel `User` Model will be set to the
LDAP users `userprincipalname` and common name (`cn`) attribute, **then saved**.

Feel free to add more attributes here, however be sure that your `users` database table contains
the key you've entered, otherwise you will receive a SQL exception upon authentication, due
to the column not existing on your users datable table.

## Attribute Handlers

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