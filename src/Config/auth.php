<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fields
    |--------------------------------------------------------------------------
    |
    | If auto connect is true, anytime Adldap is instantiated it will automatically
    | connect to your AD server. If this is set to false, you must connect manually
    | using: Adldap::connect().
    |
    */

    'fields' => [

        'unique' => [
            'email' => \Adldap\Schemas\ActiveDirectory::EMAIL,
        ],

        'information' => [
            'name' => \Adldap\Schemas\ActiveDirectory::COMMON_NAME,
        ],

    ],

];
