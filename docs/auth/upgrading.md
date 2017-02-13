# Upgrading From 2.* to 3.*
  
## Upgrade 

**Estimated Upgrade Time: 1 hour**
  
There are significant changes to the code base from `v2` to `v3`.

### PHP

Following Laravel's requirements, a minimum version of PHP 5.6 is now required.

Previously, PHP 5.5 was the minimum requirement.

### Configuration

1. Delete your `config/adldap_auth.php`
2. Run `php artisan vendor:publish --tag="adldap"`
3. Reconfigure auth driver in `config/adldap_auth.php`

#### Username Attribute

The username attribute has been renamed to the `usernames` array.

You must specify your LDAP login attribute as well as your `eloquent` attribute.

```php
// v2.0

'username_attribute' => ['email' => 'mail'],

// v3.0

'usernames' => [

    'ldap' => 'mail',
    
    'eloquent' => 'email',
    
],
```

#### Login Attribute

The configuration option `login_attribute` has been removed in favor
of the `ldap` option inside the `usernames` array.

#### Binding Users to Model

The configuration option `bind_user_to_model` has been removed
in favor of utilizing the trait itself instead. 

If you previously inserted the following trait onto your `User` model:
 
```php
Adldap\Laravel\Traits\AdldapUserModelTrait
```

You must replace this with:

```php
Adldap\Laravel\Traits\HasLdapUser
```

For example:

```php
use Adldap\Laravel\Traits\HasLdapUser;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasLdapUser;
```

You will then use the property `$user->ldap` instead of `$user->adldapUser`
to access the users LDAP model throughout your application.

#### Limitation Filter

If you were using the `limitation_filter` option, this has been replaced with the `scopes` option.

> **Note**: For more about this option, please read the [documentation](scopes.md).

You must create and define a query scope that includes your filter. For example:

```php
namespace App\Ldap\Scopes;

use Adldap\Laravel\Scopes\ScopeInterface;

class LimitationScope implements ScopeInterface
{
    public function apply($query)
    {
        $query->rawFilter('(cn=John Doe)');
    }
}
```

#### Select Attributes

The configuration option `select_attributes` has been removed.

If you would like to limit the attributes for the query on your LDAP
server when locating users, please use a [Scope](scopes.md).
