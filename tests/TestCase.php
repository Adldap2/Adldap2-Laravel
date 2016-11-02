<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\AdldapServiceProvider;
use Adldap\Laravel\AdldapAuthServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
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
