<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Login Attribute
    |--------------------------------------------------------------------------
    |
    | The login attribute is the name of the active directory user property
    | that you use to log users in. For example, if your company uses
    | email, then insert `mail`.
    |
    */

    'login_attribute' => 'samaccountname',

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
    | represents the Active Directory attribute to set it to.
    |
    | The users email is already synchronized and does not need to be
    | added to this array.
    |
    */

    'sync_attributes' => [

        'name' => 'cn',

    ],

];
