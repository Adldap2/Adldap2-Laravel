<?php

namespace Adldap\Laravel\Tests;

use Adldap\Connections\Ldap;
use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\AdldapServiceProvider;
use Adldap\Laravel\AdldapAuthServiceProvider;
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
