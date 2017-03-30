<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | This array stores the connections that are added to Adldap. You can add
    | as many connections as you like.
    |
    | The key is the name of the connection you wish to use and the value is
    | an array of configuration settings.
    |
    */

    'connections' => [

        'default' => [

            /*
            |--------------------------------------------------------------------------
            | Auto Connect
            |--------------------------------------------------------------------------
            |
            | If auto connect is true, Adldap will try to automatically connect to
            | your LDAP server in your configuration. This allows you to assume
            | connectivity rather than having to connect manually
            | in your application.
            |
            | If this is set to false, you must connect manually before running
            | LDAP operations.
            |
            */

            'auto_connect' => true,

            /*
            |--------------------------------------------------------------------------
            | Connection
            |--------------------------------------------------------------------------
            |
            | The connection class to use to run raw LDAP operations on.
            |
            | Custom connection classes must implement:
            |
            |  Adldap\Connections\ConnectionInterface
            |
            */

            'connection' => Adldap\Connections\Ldap::class,

            /*
            |--------------------------------------------------------------------------
            | Schema
            |--------------------------------------------------------------------------
            |
            | The schema class to use for retrieving attributes and generating models.
            |
            | You can also set this option to `null` to use the default schema class.
            |
            | For OpenLDAP, you must use the schema:
            |
            |   Adldap\Schemas\OpenLDAP::class
            |
            | For FreeIPA, you must use the schema:
            |
            |   Adldap\Schemas\FreeIPA::class
            |
            | Custom schema classes must implement Adldap\Schemas\SchemaInterface
            |
            */

            'schema' => Adldap\Schemas\ActiveDirectory::class,

            /*
            |--------------------------------------------------------------------------
            | Connection Settings
            |--------------------------------------------------------------------------
            |
            | This connection settings array is directly passed into the Adldap constructor.
            |
            | Feel free to add or remove settings you don't need.
            |
            */

            'connection_settings' => [

                /*
                |--------------------------------------------------------------------------
                | Account Prefix
                |--------------------------------------------------------------------------
                |
                | The account prefix option is the prefix of your user accounts in AD.
                |
                | This string is prepended to authenticating users usernames.
                |
                */

                'account_prefix' => env('ADLDAP_ACCOUNT_PREFIX', ''),

                /*
                |--------------------------------------------------------------------------
                | Account Suffix
                |--------------------------------------------------------------------------
                |
                | The account suffix option is the suffix of your user accounts in AD.
                |
                | This string is appended to authenticating users usernames.
                |
                */

                'account_suffix' => env('ADLDAP_ACCOUNT_SUFFIX', ''),

                /*
                |--------------------------------------------------------------------------
                | Domain Controllers
                |--------------------------------------------------------------------------
                |
                | The domain controllers option is an array of servers located on your
                | network that serve Active Directory. You can insert as many servers or
                | as little as you'd like depending on your forest (with the
                | minimum of one of course).
                |
                | These can be IP addresses of your server(s), or the host name.
                |
                */

                'domain_controllers' => explode(' ', env('ADLDAP_CONTROLLERS', 'corp-dc1.corp.acme.org corp-dc2.corp.acme.org')),

                /*
                |--------------------------------------------------------------------------
                | Port
                |--------------------------------------------------------------------------
                |
                | The port option is used for authenticating and binding to your AD server.
                |
                */

                'port' => env('ADLDAP_PORT', 389),

                /*
                |--------------------------------------------------------------------------
                | Timeout
                |--------------------------------------------------------------------------
                |
                | The timeout option allows you to configure the amount of time in
                | seconds that your application waits until a response
                | is received from your LDAP server.
                |
                */

                'timeout' => env('ADLDAP_TIMEOUT', 5),

                /*
                |--------------------------------------------------------------------------
                | Base Distinguished Name
                |--------------------------------------------------------------------------
                |
                | The base distinguished name is the base distinguished name you'd
                | like to perform query operations on. An example base DN would be:
                |
                |        dc=corp,dc=acme,dc=org
                |
                | A correct base DN is required for any query results to be returned.
                |
                */

                'base_dn' => env('ADLDAP_BASEDN', 'dc=corp,dc=acme,dc=org'),

                /*
                |--------------------------------------------------------------------------
                | Administrator Account Suffix / Prefix
                |--------------------------------------------------------------------------
                |
                | This option allows you to set a different account prefix and suffix
                | for your configured administrator account upon binding.
                |
                | If left empty or set to `null`, your `account_prefix` and
                | `account_suffix` options above will be used.
                |
                */

                'admin_account_prefix' => env('ADLDAP_ADMIN_ACCOUNT_PREFIX', ''),
                'admin_account_suffix' => env('ADLDAP_ADMIN_ACCOUNT_SUFFIX', ''),

                /*
                |--------------------------------------------------------------------------
                | Administrator Username & Password
                |--------------------------------------------------------------------------
                |
                | When connecting to your AD server, a username and password is required
                | to be able to query and run operations on your server(s). You can
                | use any user account that has these permissions. This account
                | does not need to be a domain administrator unless you
                | require changing and resetting user passwords.
                |
                */

                'admin_username' => env('ADLDAP_ADMIN_USERNAME', 'username'),
                'admin_password' => env('ADLDAP_ADMIN_PASSWORD', 'password'),

                /*
                |--------------------------------------------------------------------------
                | Follow Referrals
                |--------------------------------------------------------------------------
                |
                | The follow referrals option is a boolean to tell active directory
                | to follow a referral to another server on your network if the
                | server queried knows the information your asking for exists,
                | but does not yet contain a copy of it locally.
                |
                | This option is defaulted to false.
                |
                */

                'follow_referrals' => false,

                /*
                |--------------------------------------------------------------------------
                | SSL & TLS
                |--------------------------------------------------------------------------
                |
                | If you need to be able to change user passwords on your server, then an
                | SSL or TLS connection is required. All other operations are allowed
                | on unsecured protocols.
                | 
                | One of these options are definitely recommended if you 
                | have the ability to connect to your server securely.
                |
                */

                'use_ssl' => false,
                'use_tls' => false,

            ],

        ],

    ],

];
