<?php

namespace Adldap\Laravel\Tests;

use Adldap\Connections\Ldap;
use Adldap\Laravel\AdldapAuthServiceProvider;
use Adldap\Laravel\AdldapServiceProvider;
use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\Tests\Models\User;
use Adldap\Schemas\ActiveDirectory;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Create the users table for testing
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Define the environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetup($app)
    {
        // Laravel database setup.
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Adldap connection setup.
        $app['config']->set('adldap.connections.default.auto_connect', false);
        $app['config']->set('adldap.connections.default.connection', Ldap::class);
        $app['config']->set('adldap.connections.default.schema', ActiveDirectory::class);
        $app['config']->set('adldap.connections.default.connection_settings', [
            'admin_username' => 'admin',
            'admin_password' => 'password',
        ]);

        // Adldap auth setup.
        $app['config']->set('adldap_auth.bind_user_to_model', true);
        $app['config']->set('adldap_auth.username_attribute', ['email' => 'mail']);

        // Laravel auth setup.
        $app['config']->set('auth.guards.web.provider', 'adldap');
        $app['config']->set('auth.providers', [
            'adldap' => [
                'driver' => 'adldap',
                'model'  => User::class,
            ],
        ]);
    }

    /**
     * Get the package service providers required for testing.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AdldapServiceProvider::class,
            AdldapAuthServiceProvider::class,
        ];
    }

    /**
     * Get the package aliases for testing.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Adldap' => Adldap::class,
        ];
    }
}
