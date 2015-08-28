<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\AdldapServiceProvider;
use Adldap\Laravel\Facades\Adldap;
use Orchestra\Testbench\TestCase;

class FunctionalTestCase extends TestCase
{
    /**
     * Define the environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetup($app)
    {
        $app['config']->set('adldap', [
            'auto_connect' => false,
        ]);
    }

    /**
     * Get the package service providers required for testing.
     *
     * @return array
     */
    protected function getPackageProviders()
    {
        return [
            'Adldap\Laravel\AdldapServiceProvider',
        ];
    }

    /**
     * Get the package aliases for testing.
     *
     * @return array
     */
    protected function getPackageAliases()
    {
        return [
            'Adldap\Laravel\Facades\Adldap',
        ];
    }
}
