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
        $app['config']->set('adldap.connections.default.auto_connect', false);
        $app['config']->set('adldap.connections.default.connection', Ldap::class);
        $app['config']->set('adldap.connections.default.schema', ActiveDirectory::class);
        $app['config']->set('adldap.connections.default.connection_settings', [
            'admin_username' => 'admin',
            'admin_password' => 'password',
        ]);

        // Adldap auth setup.
        $app['config']->set('adldap_auth.provider', NoDatabaseUserProvider::class);

        // Laravel auth setup.
        $app['config']->set('auth.guards.web.provider', 'adldap');
        $app['config']->set('auth.providers', [
            'adldap' => [
                'driver' => 'adldap',
            ],
        ]);
    }
}
