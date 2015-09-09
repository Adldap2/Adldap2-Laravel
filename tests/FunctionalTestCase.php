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

        $app['config']->set('auth.driver', 'adldap');
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
