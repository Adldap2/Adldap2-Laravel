# Adldap2 - Laravel

[![Build Status](https://img.shields.io/travis/Adldap2/Adldap2-laravel.svg?style=flat-square)](https://travis-ci.org/Adldap2/Adldap2-laravel)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Adldap2/Adldap2-laravel/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/Adldap2/Adldap2-laravel/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)
[![Latest Stable Version](https://img.shields.io/packagist/v/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)
[![License](https://img.shields.io/packagist/l/adldap2/adldap2-laravel.svg?style=flat-square)](https://packagist.org/packages/adldap2/adldap2-laravel)

## Installation

Insert Adldap2-Laravel into your `composer.json` file:

    "adldap2/adldap2-laravel": "1.2.*",

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

Or you can inject the Adldap contract:

    use Adldap\Contracts\Adldap;
    
    class UserController extends Controller
    {
        /**
         * @var Adldap
         */
        protected $adldap;
        
        /**
         * Constructor.
         *
         * @param Adldap $adldap
         */
        public function __construct(Adldap $adldap)
        {
            $this->adldap = $adldap;
        }
        
        /**
         * Displays the all LDAP users.
         *
         * @return \Illuminate\View\View
         */
        public function index()
        {
            $users = $this->adldap->users()->all();
            
            return view('users.index', compact('users'));
        }
    }

To see more usage in detail, please visit the [Adldap2 Repository](http://github.com/Adldap2/Adldap2);

## Auth Driver

The Adldap Laravel auth driver allows you to seamlessly authenticate active directory users,
as well as have a local database record of the user. This allows you to easily attach information
to the users as you would a regular laravel application.

### Installation

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

### Usage

#### Username Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `username_attribute`. The key of the
array indicates the input name of your login form, and the value indicates the LDAP attribute that this references.

This option just allows you to set your input name to however you see fit, and allow different ways of logging in a user.

In your login form, change the username form input name to your configured input name.

By default this is set to `email`:

    <input type="text" name="email" />
    
    <input type="password" name="password" />
    
You'll also need to add the following to your AuthController if you're not overriding the default postLogin method.

	protected $username = 'email';

If you'd like to use the users `samaccountname` to login instead, just change your input name and auth configuration:

    <input type="text" name="username" />
    
    <input type="password" name="password" />

> **Note**: If you're using the `username` input field, make sure you have the `username` field inside your users database
table as well. By default, laravel's migrations use the `email` field.

Inside `config/adldap_auth.php`

    'username_attribute' => ['username' => 'samaccountname'],

> **Note** The actual authentication is done with the `login_attribute` inside your `config/adldap_auth.php` file.

#### Logging In

Login a user regularly using `Auth::attempt($credentials);`. Using `Auth::user()` when a user is logged in
will return your configured `App\User` model in `config/auth.php`.

#### Synchronizing Attributes

Inside your `config/adldap_auth.php` file there is a configuration option named `sync_attributes`. This is an array
of attributes where the key is the User model attribute, and the value is the active directory users attribute.

By default, the User models `name` attribute is synchronized to the AD users `cn` attribute. This means, upon login,
the users `name` attribute on Laravel Model will be set to the active directory common name (`cn`) attribute **then saved**.

Feel free to add more
attributes here, however be sure that your database table contains the key you've entered.

#### Binding the Adldap User Model to the Laravel User Model

> **Note**: Before we begin, enabling this option will perform a single query on your AD server for a logged in user
**per request**. Eloquent already does this for authentication, however this could lead to slightly slower load times
(depending on your AD server speed of course).

Inside your `config/adldap_auth.php` file there is a configuration option named `bind_user_to_model`. Setting this to
true sets the `adldapUser` property on your configured auth User model to the Adldap User model. For example:
    
    if(Auth::attempt($credentials))
    {
        $user = Auth::user();
        
        var_dump($user); // Returns instance of App\User;
        
        var_dump($user->adldapUser); // Returns instance of Adldap\Models\User;
        
        // Retrieving the authenticated LDAP users groups
        $groups = $user->adldapUser->getGroups();
    }
    
You **must** insert the trait `Adldap\Laravel\Traits\AdldapUserModelTrait` onto your configured auth User model, **OR**
Add the public property `adldapUser` to your model.

    // app/User.php
    
    <?php
    
    namespace App;
    
    use Adldap\Laravel\Traits\AdldapUserModelTrait;
    use Illuminate\Auth\Authenticatable;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Auth\Passwords\CanResetPassword;
    use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
    use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
    
    class User extends Model implements AuthenticatableContract, CanResetPasswordContract
    {
        use Authenticatable, CanResetPassword, AdldapUserModelTrait; // Insert trait here
    
        /**
         * The database table used by the model.
         *
         * @var string
         */
        protected $table = 'users';
    
        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = ['name', 'email', 'password'];
    
        /**
         * The attributes excluded from the model's JSON form.
         *
         * @var array
         */
        protected $hidden = ['password', 'remember_token'];
    }
