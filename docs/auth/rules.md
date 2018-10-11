# Rules

Authentication rules allow you to restrict which LDAP users are able to authenticate, much like [scopes](#scopes),
but with the ability to perform checks on the specific user authenticating, rather than a global scope.

## Creating a Rule

To create a rule, it must extend the class `Adldap\Laravel\Validation\Rules\Rule`.

Two properties will be available to you inside the rule. A `$user` property that
contains the LDAP user model, as well as their Eloquent `$model`

> **Note**: If you utilize the `NoDatabaseUserProvider` instead of the default
> `DatabaseUserProvider`, then only the `$user` property will be available.

We'll create a folder in our `app` directory containing our rule named `Rules`.

With this example rule, we only want to allow users to login if they are inside specific OU's:

```php
namespace App\Rules;

use Adldap\Laravel\Validation\Rules\Rule;

class OnlyManagersAndAccounting extends Rule
{
    /**
     * Determines if the user is allowed to authenticate.
     *
     * @return bool
     */   
    public function isValid()
    {
        $ous = [
            'ou=Accounting,dc=acme,dc=org',
            'ou=Managers,dc=acme,dc=org',
        ];
    
        return str_contains($this->user->getDn(), $ous);
    }
}
```

## Implementing the Rule

To implement your new rule, you just need to insert it into your `config/adldap_auth.php` file:

```php
'rules' => [
    
    App\Rules\OnlyManagersAndAccounting::class,

],
```

Now when you try to login, the LDAP user you login with will need to be
apart of either the `Accounting` or `Managers` Organizational Unit.

## Example Rules

### Group Validation

To validate that an authenticating user is apart of one or more LDAP groups, we can perform this with a `Rule`:

```php
namespace App\Rules;

use Adldap\Models\User as LdapUser;
use Adldap\Laravel\Validation\Rules\Rule;

class IsAccountant extends Rule
{
    /**
     * Determines if the user is allowed to authenticate.
     *
     * Only allows users in the `Accounting` group to authenticate.
     *
     * @return bool
     */   
    public function isValid()
    {
        return $this->user->inGroup('Accounting');
    }
}
```

Once you've implemented the above rule, only LDAP users that are apart of the `Accounting` group, will be allowed to authenticate.