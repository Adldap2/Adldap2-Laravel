# Introduction

The Adldap2 Laravel auth driver allows you to seamlessly authenticate LDAP users into your Laravel application.

There are two primary ways of authenticating LDAP users:

- Authenticate and synchronize LDAP users into your local applications database:

    This allows you to attach data to users as you would in a traditional application.

    Calling `Auth::user()` returns your configured Eloquent model (ex. `App\User`) of the LDAP user.
    
- Authenticate without keeping a database record for users

    This allows you to have temporary users.

    Calling `Auth::user()` returns the actual LDAP users model (ex. `Adldap\Models\User`).

We'll get into each of these methods and how to implement them, but first, lets go through the [installation guide](auth/installation.md).

## Quick Start - From Scratch

Here is a step by step guide for configuring Adldap2-Laravel (and its auth driver) with a fresh new laravel project. This guide assumes you have knowledge working with:

- Laravel
- The LDAP Protocol
- Your LDAP distro (ActiveDirectory, OpenLDAP, FreeIPA)
- Command line tools (such as Composer and Laravel's Artisan).

This guide was created with the help of [@st-claude](https://github.com/st-claude) and other awesome contributors.

1. Create a new laravel project by running the command:
  - `laravel new my-ldap-app`
  
  Or (if you don't have the [Laravel Installer](https://laravel.com/docs/5.7#installation))
 
  - `composer create-project --prefer-dist laravel/laravel my-app`.

2. Run the following command to install Adldap2-Laravel:

  - `composer require adldap2/adldap2-laravel`

3. Create a new database in your desired database interface (such as PhpMyAdmin, MySQL Workbench, command line etc.)

4. Enter your database details and credentials inside the `.env` file located in your project root directory (if there is not one there, rename the `.env.example` to `.env`).

5. If you're using username's to login users **instead** of their emails, you will need to change
   the default `email` column in `database/migrations/2014_10_12_000000_create_users_table.php`.
   
    ```php
    // database/migrations/2014_10_12_000000_create_users_table.php
       
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
          
        // From:
        $table->string('email')->unique();
          
        // To:
        $table->string('username')->unique();
          
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
    ```
   
6. Now run `php artisan migrate`.

7. Insert the following service providers in your `config/app.php` file (in the `providers` array):

    > **Note**: This step is only required for Laravel 5.0 - 5.4.
    > They are registered automatically in Laravel 5.5.

   ```php
   Adldap\Laravel\AdldapServiceProvider::class,
   Adldap\Laravel\AdldapAuthServiceProvider::class,
   ```

8. Now, insert the facade into your `config/app.php` file (in the `aliases` array):

   ```php
   'Adldap' => Adldap\Laravel\Facades\Adldap::class,
   ```

   > **Note**: Insertion of this alias in your `app.php` file isn't necessary unless you're planning on utilizing it.

9. Now run `php artisan vendor:publish` in your root project directory to publish Adldap2's configuration files.

    *  Two files will be published inside your `config` folder, `ldap.php` and `ldap_auth.php`.

10. Modify the `config/ldap.php` and `config/ldap_auth.php` files for your LDAP server configuration.

11. Run the command `php artisan make:auth` to scaffold login controllers and routes.

12. If you require logging in by another attribute, such as a username instead of email follow
the process below for your Laravel version. Otherwise ignore this step.

 **Laravel <= 5.2**

  Inside the generated `app/Http/Controllers/Auth/AuthController.php`, you'll need to add the `protected $username` property if you're logging in users by username.

  ```php
  class AuthController extends Controller
  {
      protected $username = 'username';
  ```

 **Laravel > 5.3**

  Inside the generated `app/Http/Controllers/Auth/LoginController.php`, you'll need to add the public method `username()`:

  ```php
  public function username()
  {
      return 'username';
  }
  ```

13. Now insert a new auth driver inside your `config/auth.php` file:

  ```php
  'providers' => [
      'users' => [
          'driver' => 'ldap', // Was 'eloquent'.
          'model'  => App\User::class,
      ],
  ],
  ```

14. Inside your `resources/views/auth/login.blade.php` file, if you're requiring the user logging in by username, you'll
    need to modify the HTML input to `username` instead of `email`. Ignore this step otherwise.

    From:
    ```html
    <input type="email" class="form-control" name="email" value="{{ old('email') }}">
    ```

    To:

    ```html
    <input type="text" class="form-control" name="username" value="{{ old('username') }}">
    ```

15. You should now be able to login to your Laravel application using LDAP authentication!

    If you check out your database in your `users` table, you'll see that your LDAP account was synchronized to a local user account.
    
    This means that you can attach data regularly to this user as you would with standard Laravel authentication.

    If you're having issues, and you're unable to authenticate LDAP users, please check your configuration settings inside the `ldap.php` and `ldap_auth.php` files as these directly impact your applications ability to  authenticate.

16. Congratulations, you're awesome.
