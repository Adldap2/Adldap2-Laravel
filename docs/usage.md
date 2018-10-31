# Usage

Adldap2-Laravel leverages the core [Adldap2](https://github.com/Adldap2/Adldap2) package.

When you insert the `Adldap\Laravel\AdldapServiceProvider` into your `config/app.php`, an instance of the [Adldap\Adldap](https://adldap2.github.io/Adldap2/#/setup?id=getting-started) class is created and bound as a singleton into your application.

This means, upon calling the included facade (`Adldap\Laravel\Facades\Adldap`) or interface (`Adldap\AdldapInterface`), the same instance will be returned.

This is extremely useful to know, because the `Adldap\Adldap` class is a container that stores each of your LDAP connections.

For example:

```php
use Adldap\Laravel\Facades\Adldap;

// Returns instance of `Adldap\Adldap`
$adldap = Adldap::getFacadeRoot();
```

For brevity, please take a look at the core [Adldap2 documentation](https://adldap2.github.io/Adldap2/#/setup?id=getting-started) for usage.
