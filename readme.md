<h1 align="center">Adldap2 - Laravel</h1>

<p align="center">
    <a href="https://laravel.com"><img src="https://img.shields.io/badge/Built_for-Laravel-green.svg?style=flat-square"></a>
    <a href="https://travis-ci.org/Adldap2/Adldap2-Laravel"><img src="https://img.shields.io/travis/Adldap2/Adldap2-Laravel.svg?style=flat-square"></a>
    <a href="https://scrutinizer-ci.com/g/Adldap2/Adldap2-Laravel"><img src="https://img.shields.io/scrutinizer/g/Adldap2/Adldap2-laravel/master.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/adldap2/adldap2-laravel"><img src="https://img.shields.io/packagist/dt/adldap2/adldap2-laravel.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/adldap2/adldap2-laravel"><img src="https://img.shields.io/packagist/v/adldap2/adldap2-laravel.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/adldap2/adldap2-laravel"><img src="https://img.shields.io/packagist/l/adldap2/adldap2-laravel.svg?style=flat-square"></a>
</p>

<p align="center">
    Easy configuration, access, management and authentication to LDAP servers utilizing the core
    <a href="http://www.github.com/Adldap2/Adldap2">Adldap2</a> repository.
</p>

<h4 align="center">
    <a href="http://adldap2.github.io/Adldap2-Laravel/#/?id=quick-start">Quickstart</a>
    <span> · </span>
    <a href="http://adldap2.github.io/Adldap2-Laravel/">Documentation</a>
    <span> · </span>
    <a href="http://adldap2.github.io/Adldap2/">Adldap2 Documentation (Core)</a>
</h4>

- **Authenticate LDAP users into your application.** Using the built-in authentication driver, easily allow
LDAP users to log into your application and control which users can login via [Scopes](https://adldap2.github.io/Adldap2-Laravel/#/auth/setup?id=scopes) and [Rules](https://adldap2.github.io/Adldap2-Laravel/#/auth/setup?id=rules).

- **Easily Import & Synchronize LDAP users.** Users can be imported into your database upon first login,
or you can import your entire directory via a simple [command](https://adldap2.github.io/Adldap2-Laravel/#/auth/importing): `php artisan adldap:import`.

- **Eloquent like Query Builder.** Search for LDAP records with a [fluent and easy to use interface](https://adldap2.github.io/Adldap2/#/searching) you're used to. You'll feel right at home.

- **Active Record LDAP Models.** LDAP records are returned as [individual models](https://adldap2.github.io/Adldap2/#/models/model). Easily create
and update models then persist them to your LDAP server with a simple `save()`.
