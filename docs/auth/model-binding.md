# Model Binding

Model binding allows you to attach the users LDAP model to their Eloquent
model so their LDAP data is available on every request automatically.

> **Note**: Before we begin, enabling this option will perform a single query on your LDAP server for a logged
> in user **per request**. Eloquent already does this for authentication, however
> this could lead to slightly longer load times (depending on your LDAP
> server and network speed of course).

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

Now, after you've authenticated a user (with the `ldap` auth driver),
their LDAP model will be available on their `User` model:

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