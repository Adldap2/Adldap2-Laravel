<?php

namespace Adldap\Laravel\Tests;

use Mockery as m;
use Adldap\Query\Builder;
use Adldap\Laravel\Facades\Resolver;

class ImportTest extends DatabaseTestCase
{
    public function test_importing_one_user()
    {
        $b = m::mock(Builder::class);

        $u = $this->makeLdapUser();

        $b->shouldReceive('findOrFail')->once()->with('jdoe')->andReturn($u);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('mail')
            ->shouldReceive('getEloquentUsernameAttribute')->once()->andReturn('email');

        $r = $this->artisan('adldap:import', ['user' => 'jdoe', '--no-interaction']);

        $this->assertEquals(0, $r);
        $this->assertDatabaseHas('users', ['email' => 'jdoe@email.com']);
    }

    public function test_importing_multiple_users()
    {
        $b = m::mock(Builder::class);

        $users = [
            $this->makeLdapUser([
                'samaccountname' => ['johndoe'],
                'userprincipalname' => ['johndoe@email.com'],
                'mail'           => ['johndoe@email.com'],
                'cn'             => ['John Doe'],
            ]),
            $this->makeLdapUser([
                'samaccountname' => ['janedoe'],
                'userprincipalname' => ['janedoe@email.com'],
                'mail'           => ['janedoe@email.com'],
                'cn'             => ['Jane Doe'],
            ])
        ];

        $b->shouldReceive('paginate')->once()->andReturn($b)
            ->shouldReceive('getResults')->once()->andReturn($users);

        Resolver::shouldReceive('query')->once()->andReturn($b)
            ->shouldReceive('getLdapDiscoveryAttribute')->twice()->andReturn('mail')
            ->shouldReceive('getEloquentUsernameAttribute')->twice()->andReturn('email');

        $r = $this->artisan('adldap:import', ['--no-interaction']);

        $this->assertEquals(0, $r);
        $this->assertDatabaseHas('users', ['email' => 'johndoe@email.com']);
        $this->assertDatabaseHas('users', ['email' => 'janedoe@email.com']);
    }
}
