# Adldap2 - Laravel

[![Build Status](https://img.shields.io/travis/Adldap2/Adldap2-laravel.svg?style=flat-square)](https://travis-ci.org/Adldap2/Adldap2-laravel)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Adldap2/Adldap2-laravel/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/Adldap2/Adldap2-laravel/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)
[![Latest Stable Version](https://img.shields.io/packagist/v/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)
[![License](https://img.shields.io/packagist/l/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)

## Installation

Insert Adldap2-Laravel into your `composer.json` file:

    "adldap2\adldap2-laravel": "1.1.*",

Then run `composer update`.

Once finished, insert the service provider in your `config/app.php` file:

    Adldap\Laravel\AdldapServiceProvider::class,
    
Then insert the facade:

    'Adldap' => Adldap\Laravel\Facades\Adldap::class

Publish the configuration file by running:

    php artisan vendor:publish

Now you're all set!

## Usage

You can perform all methods on Adldap through its facade like so:

    $user = Adldap::users()->find('john doe');
    
    $search = Adldap::search()->where('cn', '=', 'John Doe')->get();
    
    
    if(Adldap::authenticate($username, $password))
    {
        // Passed!
    }

To see more usage in detail, please visit the [Adldap2 Repository](http://github.com/Adldap2/Adldap2);


## Auth Driver

The Adldap Laravel auth driver allows you to seamlessly authenticate active directory users,
as well as have a local database record of the user. This allows you to easily attach information
to the users as you would a regular laravel application.

Insert the `AdldapAuthServiceProvider` into your `config/app.php` file:

    Adldap\Laravel\AdldapAuthServiceProvider::class,
    
Publish the auth configuration:

    php artisan vendor:publish
    
Change the auth driver in `config/auth.php` to `adldap`:

    /*
    |--------------------------------------------------------------------------
    | Default Authentication Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the authentication driver that will be utilized.
    | This driver manages the retrieval and authentication of the users
    | attempting to get access to protected areas of your application.
    |
    | Supported: "database", "eloquent"
    |
    */

    'driver' => 'adldap',

In your login form, change the username form input name to the active directory attribute you'd like to
use to retrieve users from. For example, if I'd like to login users by their email address, then use the
form input name `mail`:

    <input type="text" name="mail" />
    
    <input type="password" name="password" />

Or, use their `samaccountname` attribute:

    <input type="text" name="samaccountname" />
    
    <input type="password" name="password" />
    
All this name represents, is how Adldap discovers the user trying to login. The actual authentication is done
with the `login_attribute` inside your `config/adldap_auth.php` file.

Login a user regularly using `Auth::attempt($credentials);`. Using `Auth::user()` when a user is logged in
will return your configured `App\User` model in `config/auth.php`.
