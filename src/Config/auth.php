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
    | Rules
    |--------------------------------------------------------------------------
    |
    | Rules allow you to deny authentication requests depending on scenarios.
    |
    | You can create your own rules and insert them here.
    |
    | All rules must extend from Adldap\Laravel\Validation\Rules\Rule.
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
    | Username Attribute
    |--------------------------------------------------------------------------
    |
    | The username attribute is an array of the HTML input name and the LDAP
    | attribute to discover the user by. The reason for this is to allow
    | the ability for users to login via different attributes.
    |
    | For example, if your HTML input name is `email` and you'd like users to login
    | by their LDAP `mail` attribute, then keep the configuration below. However,
    | if you'd like to login users by their usernames, then change `mail`
    | to `samaccountname`. and `email` to `username`.
    |
    | This must be an array with a key - value pair.
    |
    */

    'username_attribute' => ['email' => 'mail'],

    /*
    |--------------------------------------------------------------------------
    | Login Attribute
    |--------------------------------------------------------------------------
    |
    | The login attribute is the name of the LDAP users attribute that you use
    | to authenticate against your LDAP server.
    |
    | This is usually the users `sAMAccountName`.
    |
    | This option must be a string.
    |
    */

    'login_attribute' => env('ADLDAP_LOGIN_ATTRIBUTE', 'samaccountname'),

    /*
    |--------------------------------------------------------------------------
    | Password Key
    |--------------------------------------------------------------------------
    |
    | The password field is the name of the HTML input that contains the users password.
    |
    | Change this if you change your HTML password fields input name.
    |
    | This option must be a string.
    |
    */

    'password_field' => env('ADLDAP_PASSWORD_KEY', 'password'),

    /*
    |--------------------------------------------------------------------------
    | Limitation Filter
    |--------------------------------------------------------------------------
    |
    | The limitation filter allows you to enter a raw filter to only allow
    | specific users / groups / ous to authenticate.
    |
    | For an example, to only allow users inside of a group
    | named 'Accounting', you would insert the Accounting
    | groups full distinguished name inside the filter:
    |
    | '(memberof=cn=Accounting,dc=corp,dc=acme,dc=org)'
    |
    | This value must be a standard LDAP filter.
    |
    */

    'limitation_filter' => env('ADLDAP_LIMITATION_FILTER', ''),

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
    | Allow Only Imported
    |--------------------------------------------------------------------------
    |
    | This option allows only pre-imported LDAP users to authenticate.
    |
    | To import a specific user, use the command:
    |
    |   php artisan adldap:import {username}
    |
    */

    'allow_only_imported' => env('ADLDAP_ALLOW_ONLY_IMPORTED', false),

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
    | represents the LDAP attribute to set it to.
    |
    | Your login attribute (configured above) is already synchronized
    | and does not need to be added to this array.
    |
    | This option must be an array and is only applicable
    | to the DatabaseUserProvider.
    |
    */

    'sync_attributes' => [

        'name' => 'cn',

    ],

];
