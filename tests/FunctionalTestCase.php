<?php

namespace Adldap\Laravel\Tests;

use Orchestra\Testbench\TestCase;

class FunctionalTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__.'/stubs/migrations'),
        ]);
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
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Adldap' => 'Adldap\Laravel\Facades\Adldap',
        ];
    }
}
