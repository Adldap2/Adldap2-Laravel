# Setup

## Configuration

Upon publishing your `ldap.php` configuration, you'll see an array named `connections`. This
array contains a key value pair for each LDAP connection you're looking to configure.

Each connection you configure should be separate domains. Only one connection is necessary
when using multiple LDAP servers on the same domain.

### Connection Name

The `default` key is your LDAP connections name. This is used as an identifier when connecting.

Usually this is set to your domain name. For example:

```php
'connections' => [
    'corp.acme.org' => [
        '...',
    ],
],
```

You may change this to whatever name you prefer.

### Auto Connect

The `auto_connect` configuration option determines whether Adldap2-Laravel will try to bind to your
LDAP server automatically using your configured credentials when calling the `Adldap`
facade or injecting the `AdldapInterface` interface.

For the example below, notice how we don't have to connect manually and we can assume connectivity:

```php
use Adldap\AdldapInterface;

public class UserController extends Controller
{
    public function index(AdldapInterface $ldap)
    {
        return view('users.index', [
            'users' => $ldap->search()->users()->get();
        ]);
    }
}
```

If this is set to `false`, you **must** connect manually before running operations on your server.
Otherwise, you will receive an exception upon performing operations.

### Settings

The `settings` option contains a configuration array of your LDAP server connection.

Please view the core [Aldap2 Configuration Guide](https://adldap2.github.io/Adldap2/#/setup?id=options)
for definitions on each option and its meaning.

Once you've done so, you're ready to move to the [usage guide](usage.md).
