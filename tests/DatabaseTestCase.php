<?php

namespace Adldap\Laravel\Tests;

use Adldap\Connections\Ldap;
use Adldap\Schemas\ActiveDirectory;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseTestCase extends TestCase
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
            $table->softDeletes();
        });

        Hash::setRounds(4);
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
            'schema' => ActiveDirectory::class,
        ]);

        // Adldap auth setup.
        $config->set('ldap_auth.provider', DatabaseUserProvider::class);
        $config->set('ldap_auth.sync_attributes', [
            'email' => 'userprincipalname',
            'name' => 'cn',
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
}
