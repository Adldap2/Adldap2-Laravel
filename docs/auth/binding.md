# Binding the Adldap2 User Model to the Laravel User Model

> **Note**: Before we begin, enabling this option will perform a single query on your AD server for a logged in user
**per request**. Eloquent already does this for authentication, however this could lead to slightly longer load times
(depending on your AD server and network speed of course).

To begin, insert the `Adldap\Laravel\Traits\HasLdapUser` trait onto your `User` model:

```php
namespace App;

use Adldap\Laravel\Traits\HasLdapUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes, HasLdapUser;
```

Now, after you've authenticated a user via the `adldap` driver, their LDAP model is available on their `User` model:

```php    
if (Auth::attempt($credentials)) {
    $user = Auth::user();
    
    var_dump($user); // Returns instance of App\User;
    
    var_dump($user->ldap); // Returns instance of Adldap\Models\User;
   
    // Examples:
    
    $user->ldap->getGroups();
    
    $user->ldap->getCommonName();
    
    $user->ldap->getConvertedSid();
}
```