<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Tests\Models\TestUser;
use Illuminate\Support\Facades\Auth;

class EloquentAuthenticateTest extends DatabaseTestCase
{
    /** @test */
    public function it_does_not_set_the_ldap_user_if_the_auth_provider_is_not_adldap()
    {
        $this->app['config']->set('auth.guards.web.provider', 'users');

        $user = $this->makeLdapUser([
            'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'cn'                => ['John Doe'],
            'userprincipalname' => ['jdoe@email.com'],
        ]);

        $importer = new Import($user, new TestUser());

        $model = $importer->handle();

        Resolver::spy();
        Resolver::shouldNotReceive('byModel');

        Auth::login($model);
    }
}
