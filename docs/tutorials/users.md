# User Tutorials

> **Notice**: These tutorials have been created using ActiveDirectory.
> Some tutorials may not relate to your LDAP distribution.

> **Note**: You cannot create or modify user passwords without
> connecting to your LDAP server via SSL or TLS.

## Creating Users

To begin, creating a user is actually quite simple, as it only requires a Common Name:

```php
$user = Adldap::make()->user([
    'cn' => 'John Doe',
]);

$user->save();
```

If you'd like to provide more attributes, simply add more:

```php
$user = Adldap::make()->user([
    'cn' => 'John Doe',
    'sn' => 'Doe',
    'givenname' => 'John',
    'department' => 'Accounting'
]);

$user->save();
```

If you don't provide a Distinguished Name to the user during creation, one will be set for you automatically
by taking your configured `base_dn` and using the users Common Name you give them:

```php
$user = Adldap::make()->user([
    'cn' => 'John Doe',
]);

$user->save(); // Creates a user with the DN: 'cn=John Doe,dc=acme,dc=org'
```

You can provide a `dn` attribute to set the users Distinguished Name you would like to use for creation:

```php
$user = Adldap::make()->user([
    'cn' => 'John Doe',
    'dn' => 'cn=John Doe,ou=Users,dc=acme,dc=com'
]);

$user->save();
```

All users created in your directory will be disabled by default. How do we enable these users upon creation and set thier password?

What we can use is the `Adldap\Models\Attributes\AccountControl` attribute class and the `userPassword` attribute.

```php
// Encode the users password.
$password = Adldap\Utilies::encodePassword('super-secret');

// Create a new AccountControl object.
$uac = new Adldap\Models\Attributes\AccountControl();

// Set the UAC value to '512'.
$uac->accountIsNormal();

$user = Adldap::make()->user([
    'cn' => 'John Doe',
    'dn' => 'cn=John Doe,ou=Users,dc=acme,dc=com'
    'userPassword' => $password,
    'userAccountControl' => $uac->getValue(),
]);

$user->save();
```

You can also fluently create accounts using setter methods if you'd prefer:

> **Note**: There are some conveniences that come with using the setter methods.
> Notice how you don't have to encode the password using the `setPassword()`
> method or call `getValue()` when setting the users account control.

```php
// Create a new AccountControl object.
$uac = new Adldap\Models\Attributes\AccountControl();

$uac->accountIsNormal();

$user = Adldap::make()->user();

$user
    ->setCommonName('John Doe')
    ->setDn('cn=John Doe,ou=Users,dc=acme,dc=com')
    ->setPassword('super-secret')
    ->setUserAccountControl($uac);

$user->save();
```

## Modifying Users

You can modify users in a variety of ways. Each way will be shown below.
Use whichever ways you prefer readable and most clear to you.

To modify users, you simply modify their attributes using dynamic properties and `save()` them:

```php
$jdoe = Adldap::search()->users()->find('jdoe');

$uac = new Adldap\Models\Attributes\AccountControl();

$uac->accountIsNormal();

$jdoe->deparment = 'Accounting';
$jdoe->telephoneNumber = '555 555-5555';
$jdoe->mobile = '555 444-4444';
$jdoe->userAccountControl = $uac->getValue();

$jdoe->save();
```

You can also use 'setter' methods to perform the same task.

```php
$jdoe = Adldap::search()->users()->find('jdoe');

$uac = new Adldap\Models\Attributes\AccountControl();

$uac->accountIsNormal();

$jdoe
    ->setDepartment('Accounting');
    ->setTelephoneNumber('555 555-5555')
    ->setMobileNumber('555 555-5555')
    ->setUserAccountControl($uac);

$jdoe->save();
```

Using setter methods offer a little bit of benefit, for example you can see above that
`$uac->getValue()` does not need to be called as the `setUserAccountControl()` method
will automatically convert an `AccountControl` object to its integer value.

Setter methods are also chainable (if you prefer that syntax).

## Deleting Users

As any other returned model in Adldap2, you can call the `delete()` method
to delete a user from your directory:

```php
$user = Adldap::search()->find('jdoe');

$user->delete();
```

Once you `delete()` a user (successfully), the `exists` property on their model is set to `false`:

```php
$user = Adldap::search()->find('jdoe');

$user->delete();

var_dump($user->exists); // Returns 'bool(false)'
```
