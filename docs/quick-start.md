# Quick Start - From Scratch

Here is a step by step guide for configuring Adldap2-Laravel (and its auth driver) with a fresh new laravel project. This guide assumes you have
knowledge working with Laravel, Active Directory, LDAP Protocol and command line tools (such as Composer and Artisan).

This guide was created with the help of [@st-claude](https://github.com/st-claude) and other awesome contributors.

1. Create a new laravel project by running the command:
  - `laravel new my-app`
  
  Or (if you don't have the [Laravel Installer](https://laravel.com/docs/5.2#installation))
 
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

    *  Two files will be published inside your `config` folder, `adldap.php` and `adldap_auth.php`.

10. Modify the `config/adldap.php` and `config/adldap_auth.php` files for your LDAP server configuration.

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
          'driver' => 'adldap', // Was 'eloquent'.
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

15. You should now be able to login to your Laravel application using LDAP authentication! If you check out your database
  in your `users` table, you'll see that your LDAP account was synchronized to a local user account. This means that
  you can attach data regularly to this user as you would with standard Laravel authentication.

16. Congratulations, you're awesome.
