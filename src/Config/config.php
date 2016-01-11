<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto Connect
    |--------------------------------------------------------------------------
    |
    | If auto connect is true, anytime Adldap is instantiated it will automatically
    | connect to your AD server. If this is set to false, you must connect manually
    | using: Adldap::connect().
    |
    */

    'auto_connect' => true,

    /*
    |--------------------------------------------------------------------------
    | Connection
    |--------------------------------------------------------------------------
    |
    | The connection class to use to run operations on.
    |
    | Custom connection classes must implement \Adldap\Connections\ConnectionInterface
    |
    */

    'connection' => Adldap\Connections\Ldap::class,

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
        | Account Suffix
        |--------------------------------------------------------------------------
        |
        | The account suffix option is the suffix of your user accounts in AD.
        |
        | For example, if your domain DN is DC=corp,DC=acme,DC=org, then your
        | account suffix would be @corp.acme.org. This is then appended to
        | then end of your user accounts on authentication.
        |
        */

        'account_suffix' => '@acme.org',

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
        */

        'domain_controllers' => ['corp-dc1.corp.acme.org', 'corp-dc2.corp.acme.org'],

        /*
        |--------------------------------------------------------------------------
        | Port
        |--------------------------------------------------------------------------
        |
        | The port option is used for authenticating and binding to your AD server.
        |
        */

        'port' => 389,

        /*
        |--------------------------------------------------------------------------
        | Base Distinguished Name
        |--------------------------------------------------------------------------
        |
        | The base distinguished name is the base distinguished name you'd like
        | to perform operations on. An example base DN would be DC=corp,DC=acme,DC=org.
        |
        | If one is not defined, then Adldap will try to find it automatically
        | by querying your server. It's recommended to include it to
        | limit queries executed per request.
        |
        */

        'base_dn' => 'dc=corp,dc=acme,dc=org',

        /*
        |--------------------------------------------------------------------------
        | Administrator Username & Password
        |--------------------------------------------------------------------------
        |
        | When connecting to your AD server, an administrator username and
        | password is required to be able to query and run operations on
        | your server(s). You can use any user account that has
        | these permissions.
        |
        */

        'admin_username' => 'username',
        'admin_password' => 'password',

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
        | on unsecured protocols. One of these options are definitely recommended
        | if you have the ability to connect to your server securely.
        |
        */

        'use_ssl' => false,
        'use_tls' => false,

        /*
        |--------------------------------------------------------------------------
        | SSO (Single Sign On)
        |--------------------------------------------------------------------------
        |
        | If you enable single sign on, be sure you've properly set it up
        | on your server before hand.
        |
        */

        'use_sso' => false,

    ],

];
