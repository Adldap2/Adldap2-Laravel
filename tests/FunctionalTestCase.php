<?php

namespace Adldap\Laravel\Tests;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class FunctionalTestCase extends TestCase
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
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('adldap.auto_connect', false);
        $app['config']->set('adldap_auth.bind_user_to_model', true);

        $app['config']->set('auth.guards.web.provider', 'adldap');

        $app['config']->set('auth.providers', [
            'adldap' => [
                'driver' => 'adldap',
                'model'  => 'Adldap\Laravel\Tests\Models\User',
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
            'Adldap\Laravel\AdldapServiceProvider',
            'Adldap\Laravel\AdldapAuthServiceProvider',
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
            'Adldap' => 'Adldap\Laravel\Facades\Adldap',
        ];
    }
}
