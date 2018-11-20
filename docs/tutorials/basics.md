# The Basics

Lets get down to the basics. This guide will help you get a quick understanding of
using Adldap2 and cover some use cases you might want to learn how to
perform before trying to work it out on your own.

## Searching

### Querying for your Base DN

If you're not sure what your base distinguished name should be, you can use the query
builder to locate it for you if you're making a successful connection to the server:

```php
$base = Adldap::search()->findBaseDn();

echo $base; // Returns 'dc=corp,dc=acme,dc=org'
```

### Querying for Enabled / Disabled Users

To locate enabled / disabled users in your directory, call the `whereEnabled()`
and `whereDisabled()` methods on a query:

```php
$enabledUsers = Adldap:search()->users()->whereEnabled()->get();

$disabledUsers = Adldap:search()->users()->whereDisabled()->get();
```

### Querying for Group Membership

To locate records in your directory that are apart of a group, use the `whereMemberOf()` query method:

```php
// First, locate the group we want to retrieve the members for:
$accounting = Adldap::search()->groups()->find('Accounting');

// Retrieve the members that belong to the above group.
$results = Adldap::search()->whereMemberOf($accounting)->get();

// Iterate through the results:
foreach ($results as $model) {
    $model->getCommonName(); // etc.
}
```

### Escaping Input Manually

If you'd like to execute raw filters, it's best practice to escape any input you receive from a user.

You can do this in a couple ways, so use whichever feels best to you.

Each escape method below will escape all characters inputted unless an **ignore** parameter or **flag**
parameter have been given (such as `LDAP_ESCAPE_FITLER` or `LDAP_ESCAPE_DN`).

```php
// Escaping using the query builder:
$escaped = Adldap::search()->escape($input, $ignore = '', $flags = 0);

// Escaping using the `Utilities` class:
$escaped = \Adldap\Utilities::escape($input, $ignore = '', $flags = 0);

// Escaping with the native PHP:
$escaped = ldap_escape($input, $ignore = '', $flags = 0);

$rawFilter = `(samaccountname=$escaped)`

$results = Adldap::search()->rawFilter($rawFilter)->get();
```

## Models

### Creating

### Updating

### Deleting

### Moving

### Renaming
