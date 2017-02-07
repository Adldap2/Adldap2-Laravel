<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connection
    |--------------------------------------------------------------------------
    |
    | The LDAP connection to use for laravel authentication.
    |
    | You must specify connections in your `config/adldap.php` configuration file.
    |
    | This must be a string.
    |
    */

    'connection' => env('ADLDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    |
    | The LDAP authentication provider to use depending
    | if you require database synchronization.
    |
    | For synchronizing LDAP users to your local applications database, use the provider:
    |
    | Adldap\Laravel\Auth\DatabaseUserProvider::class
    |
    | Otherwise, if you just require LDAP authentication, use the provider:
    |
    | Adldap\Laravel\Auth\NoDatabaseUserProvider::class
    |
    */

    'provider' => Adldap\Laravel\Auth\DatabaseUserProvider::class,

    /*
    |--------------------------------------------------------------------------
    | Resolver
    |--------------------------------------------------------------------------
    |
    | The resolver that locates users from your LDAP server.
    |
    | Custom resolvers must implement the following interface:
    |
    |   Adldap\Laravel\Auth\ResolverInterface
    |
    */

    'resolver' => Adldap\Laravel\Auth\Resolver::class,

    /*
    |--------------------------------------------------------------------------
    | Importer
    |--------------------------------------------------------------------------
    |
    | The importer that imports LDAP users into your local database.
    |
    | Custom importers must implement the following interface:
    |
    |   Adldap\Laravel\Auth\ImporterInterface
    |
    */

    'importer' => Adldap\Laravel\Auth\Importer::class,

    /*
    |--------------------------------------------------------------------------
    | Rules
    |--------------------------------------------------------------------------
    |
    | Rules allow you to control user authentication requests depending on scenarios.
    |
    | You can create your own rules and insert them here.
    |
    | All rules must extend from the following class:
    |
    |   Adldap\Laravel\Validation\Rules\Rule
    |
    */

    'rules' => [

        // Denys deleted users from authenticating.

        Adldap\Laravel\Validation\Rules\DenyTrashed::class,

        // Allows only manually imported users to authenticate.

        // Adldap\Laravel\Validation\Rules\OnlyImported::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes allow you to restrict the LDAP query that locates
    | users upon import and authentication.
    |
    | All scopes must implement the following interface:
    |
    |   Adldap\Laravel\Scopes\ScopeInterface
    |
    */

    'scopes' => [

        // Only allows users with a user principal name to authenticate.

        Adldap\Laravel\Scopes\UpnScope::class,

    ],

    'usernames' => [

        /*
        |--------------------------------------------------------------------------
        | LDAP
        |--------------------------------------------------------------------------
        |
        | This is the LDAP users attribute that you use to authenticate
        | against your LDAP server. This is usually the users
        |'sAMAccountName' / 'userprincipalname' attribute.
        |
        | If you'd like to use their username to login instead, insert `samaccountname`.
        |
        */

        'ldap' => 'userprincipalname',

        /*
        |--------------------------------------------------------------------------
        | Eloquent
        |--------------------------------------------------------------------------
        |
        | This is the attribute that is used for locating
        | and storing the LDAP username above.
        |
        | If you're using a `username` field instead, change this to `username`.
        |
        | This option is only applicable to the DatabaseUserProvider.
        |
        */

        'eloquent' => 'email',

    ],

    /*
    |--------------------------------------------------------------------------
    | Login Fallback
    |--------------------------------------------------------------------------
    |
    | The login fallback option allows you to login as a user located on the
    | local database if active directory authentication fails.
    |
    | Set this to true if you would like to enable it.
    |
    | This option must be true or false and is only
    | applicable to the DatabaseUserProvider.
    |
    */

    'login_fallback' => env('ADLDAP_LOGIN_FALLBACK', false),

    /*
    |--------------------------------------------------------------------------
    | Password Sync
    |--------------------------------------------------------------------------
    |
    | The password sync option allows you to automatically synchronize
    | users AD passwords to your local database. These passwords are
    | hashed natively by laravel using the bcrypt() method.
    |
    | Enabling this option would also allow users to login to their
    | accounts using the password last used when an AD connection
    | was present.
    |
    | If this option is disabled, the local user account is applied
    | a random 16 character hashed password, and will lose access
    | to this account upon loss of AD connectivity.
    |
    | This option must be true or false and is only applicable
    | to the DatabaseUserProvider.
    |
    */

    'password_sync' => env('ADLDAP_PASSWORD_SYNC', true),

    /*
    |--------------------------------------------------------------------------
    | Windows Auth Attribute
    |--------------------------------------------------------------------------
    |
    | This array represents how a user is found when
    | utilizing the Adldap Windows Auth Middleware.
    |
    | The key of the array represents the attribute that the user is located by.
    |
    |     For example, if 'samaccountname' is the key, then your LDAP server is
    |     queried for a user with the 'samaccountname' equal to the value of
    |     $_SERVER['AUTH_USER'].
    |
    |     If a user is found, they are imported (if using the DatabaseUserProvider)
    |     into your local database, then logged in.
    |
    | The value of the array represents the 'key' of the $_SERVER
    | array to pull the users username from.
    |
    |    For example, $_SERVER['AUTH_USER'].
    |
    | This must be an array with a key - value pair.
    |
    */

    'windows_auth_attribute' => ['samaccountname' => 'AUTH_USER'],

    /*
    |--------------------------------------------------------------------------
    | Sync Attributes
    |--------------------------------------------------------------------------
    |
    | Attributes specified here will be added / replaced on the user model
    | upon login, automatically synchronizing and keeping the attributes
    | up to date.
    |
    | The array key represents the Laravel model key, and the value
    | represents the users LDAP attribute.
    |
    | This option must be an array and is only applicable
    | to the DatabaseUserProvider.
    |
    */

    'sync_attributes' => [

        'email' => 'userprincipalname',
        'name' => 'cn',

    ],

];
