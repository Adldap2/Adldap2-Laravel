# Rules

Authentication rules allow you to restrict which LDAP users are able to authenticate, much like [scopes](docs/scopes.md),
but with the ability to perform checks on the specific user authenticating.

## Creating a Rule

To create a rule, it must extend the class `Adldap\Laravel\Validation\Rules\Rule`.

Two properties will be available to you inside the rule. A `$user` property that
contains the LDAP user model, as well as their Eloquent `$model`

> **Note**: If you utilize the `NoDatabaseUserProvider` instead of the default
> `DatabaseUserProvider`, then only the `$user` property will be available.

We'll create a folder in our `app` directory containing our rule named `Rules`.

With this example rule, we only want to allow LDAP users with the last name of `Doe` or if their Eloquent model was created after 2016:

```php
namespace App\Rules;

use Adldap\Laravel\Validation\Rules\Rule;

class DoeRule extends Rule
{
    /**
     * The LDAP user.
     *
     * @var User
     */
    protected $user;
    
    /**
     * The Eloquent model.
     *
     * @var Model|null
     */
    protected $model;
    
    /**
     * Determines if the user is allowed to authenticate.
     *
     * @return bool
     */   
    public function isValid()
    {
        return $this->user->getLastName() == 'Doe' || $this->model->year > '2016';
    }
}
```

## Implementing the Rule

To implement your new rule, you just need to insert it into your `config/adldap_auth.php` file:

```php
'rules' => [

    // Denys deleted users from authenticating.

    Adldap\Laravel\Validation\Rules\DenyTrashed::class,

    // Allows only manually imported users to authenticate.

    // Adldap\Laravel\Validation\Rules\OnlyImported::class,
    
    App\Rules\DoeRule::class,

],
```

Now when you try to authenticate, you will either need to be logging in with an LDAP user with the last name of `Doe` or 
with a local database record that was created after 2016.

## Example Rules

### Group Validation

To validate that an authenticating user is apart of one or more LDAP groups, we can perform this with a `Rule`:

```php
namespace App\Rules;

use Adldap\Models\User as LdapUser;
use Adldap\Laravel\Validation\Rules\Rule;

class AccountingRule extends Rule
{
    /**
     * The LDAP user.
     *
     * @var User
     */
    protected $user;
    
    /**
     * The Eloquent model.
     *
     * @var Model|null
     */
    protected $model;
    
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
