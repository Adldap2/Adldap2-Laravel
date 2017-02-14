# Scopes

Authentication scopes allow you to restrict which LDAP users are allowed to authenticate.

If you're familiar with Laravel's [Query Scopes](https://laravel.com/docs/5.4/eloquent#query-scopes),
then these will feel very similar.

## Creating a Scope

To create a scope, it must implement the interface `Adldap\Laravel\Scopes\ScopeInterface`.

For this example, we'll create a folder inside our `app` directory containing our scope named `Scopes`.

Of course, you can place these scopes wherever you desire, but in this example, our final scope path will be:

```
../my-application/app/Scopes/AccountingScope.php
```

With this scope, we want to only allow members of an Active Directory group named `Accounting`:


```php
namespace App\Scopes;

use Adldap\Query\Builder;
use Adldap\Laravel\Scopes\ScopeInterface;

class AccountingScope implements ScopeInterface
{
    /**
     * Apply the scope to a given LDAP query builder.
     *
     * @param Builer $builder
     *
     * @return void
     */
    public function apply(Builder $query)
    {
        // The distinguished name of our LDAP group.
        $accounting = 'cn=Accounting,ou=Groups,dc=acme,dc=org';
        
        $query->whereMemeberOf($accouning);
    }
}
```

## Implementing a Scope

Now that we've created our scope (`app/Scopes/AccountingScope.php`), we can insert it into our `config/adldap_auth.php` file:

```php
'scopes' => [

    // Only allows users with a user principal name to authenticate.

    Adldap\Laravel\Scopes\UpnScope::class,
    
    // Only allow members of 'Accounting' to login.
    
    App\Scopes\AccountingScope::class,

],
```

Once you've inserted your scope into the configuration file, you will now only be able
to authenticate with users that are a member of the `Accounting` group.

All other users will be denied authentication to your app,
even if their credentials are valid.

> **Note**: If you're caching your configuration files, make sure you run `php artisan config:clear`.
