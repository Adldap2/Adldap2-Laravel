<?php

namespace Adldap\Laravel\Tests;

use Adldap\Connections\Ldap;
use Adldap\Laravel\AdldapAuthServiceProvider;
use Adldap\Laravel\AdldapServiceProvider;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Schemas\ActiveDirectory;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        Hash::setRounds(4);

        return $app;
    }

    protected function getPackageProviders($app)
    {
        return [
            AdldapServiceProvider::class,
            AdldapAuthServiceProvider::class,
        ];
    }

    /**
     * Define the environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetup($app)
    {
        $config = $app['config'];

        // Laravel database setup.
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Adldap connection set$configup.
        $config->set('ldap.connections.default.auto_connect', false);
        $config->set('ldap.connections.default.connection', Ldap::class);
        $config->set('ldap.connections.default.settings', [
            'username' => 'admin@email.com',
            'password' => 'password',
            'schema'   => ActiveDirectory::class,
        ]);

        // Adldap auth setup.
        $config->set('ldap_auth.provider', DatabaseUserProvider::class);
        $config->set('ldap_auth.sync_attributes', [
            'email' => 'userprincipalname',
            'name'  => 'cn',
        ]);

        // Laravel auth setup.
        $config->set('auth.guards.web.provider', 'ldap');
        $config->set('auth.providers', [
            'ldap' => [
                'driver' => 'ldap',
                'model'  => TestUser::class,
            ],
            'users'  => [
                'driver' => 'eloquent',
                'model'  => TestUser::class,
            ],
        ]);
    }

    /**
     * Returns a new LDAP user model.
     *
     * @param array $attributes
     *
     * @return \Adldap\Models\User
     */
    protected function makeLdapUser(array $attributes = [])
    {
        return Adldap::getDefaultProvider()->make()->user($attributes ?: [
            'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'samaccountname'    => ['jdoe'],
            'userprincipalname' => ['jdoe@email.com'],
            'mail'              => ['jdoe@email.com'],
            'cn'                => ['John Doe'],
        ]);
    }

    /**
     * Returns a mock LDAP connection object.
     *
     * @param array $methods
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockConnection($methods = [])
    {
        $defaults = ['isBound', 'search', 'getEntries', 'bind', 'close'];

        $connection = $this->getMockBuilder(Ldap::class)
            ->setMethods(array_merge($defaults, $methods))
            ->getMock();

        Adldap::getDefaultProvider()->setConnection($connection);

        return $connection;
    }
}
