<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Username Attribute
    |--------------------------------------------------------------------------
    |
    | The username attribute is an array of the html input name and the LDAP
    | attribute to discover the user by. The reason for this is to hide
    | the attribute that you're using to login users.
    |
    | For example, if your input name is `username` and you'd like users
    | to login by their `samaccountname` attribute, then keep the
    | configuration below. However, if you'd like to login users
    | by their emails, then change `samaccountname` to `mail`.
    | and `username` to `email`.
    |
    */

    'username_attribute' => ['username' => 'samaccountname'],

    /*
    |--------------------------------------------------------------------------
    | Password Key
    |--------------------------------------------------------------------------
    |
    | The password key is the name of the input array key located inside
    | the user input array given to the auth driver.
    |
    | Change this if you change your password fields input name.
    |
    */

    'password_key' => 'password',

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
    | Bind User to Model
    |--------------------------------------------------------------------------
    |
    | The bind User to Model option allows you to access the Adldap user model
    | instance on your laravel database model to be able run operations
    | or retrieve extra attributes on the Adldap user model instance.
    |
    | If this option is true, you must insert the trait:
    |
    |   `Adldap\Laravel\Traits\AdldapUserModelTrait`
    |
    | Onto your User model configured in `config/auth.php`.
    |
    | Then use `Auth::user()->adldapUser` to access.
    |
    */

    'bind_user_to_model' => false,

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
    */

    'sync_attributes' => [

        'name' => 'cn',

    ],
    
    /*
    |--------------------------------------------------------------------------
    | Sync Attributes on empty
    |--------------------------------------------------------------------------
    |
    | Attributes specified here will be added on the user model
    | upon login, for empty field.
    |
    | The array key represents the Laravel model key, and the value
    | represents the Active Directory attribute to set it to.
    |
    */

    'sync_attributes_on_empty' => [

        'email' => 'mail',

    ],

    /*
    |--------------------------------------------------------------------------
    | Select Attributes
    |--------------------------------------------------------------------------
    |
    | Attributes to select upon the user on authentication and binding.
    |
    | If no attributes are given inside the array, all attributes on the
    | user are selected.
    |
    | ** Note ** : Keep in mind you must include attributes that you would
    | like to synchronize, as well as your login attribute.
    |
    */

    'select_attributes' => [

        //

    ],

];
