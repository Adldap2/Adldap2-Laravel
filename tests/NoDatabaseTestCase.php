<?php

namespace Adldap\Laravel\Tests;

use Adldap\Connections\Ldap;
use Adldap\Schemas\ActiveDirectory;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;

class NoDatabaseTestCase extends TestCase
{
    /**
     * Define the environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetup($app)
    {
        // Adldap connection setup.
        $app['config']->set('ldap.connections.default.auto_connect', false);
        $app['config']->set('ldap.connections.default.connection', Ldap::class);
        $app['config']->set('ldap.connections.default.settings', [
            'username' => 'admin',
            'password' => 'password',
            'schema'   => ActiveDirectory::class,
        ]);

        // Adldap auth setup.
        $app['config']->set('ldap_auth.provider', NoDatabaseUserProvider::class);

        // Laravel auth setup.
        $app['config']->set('auth.guards.web.provider', 'ldap');
        $app['config']->set('auth.providers', [
            'ldap' => [
                'driver' => 'ldap',
            ],
        ]);
    }
}
