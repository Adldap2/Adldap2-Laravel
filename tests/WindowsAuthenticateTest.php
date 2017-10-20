<?php

namespace Adldap\Laravel\Tests;

use Mockery as m;
use Adldap\Query\Builder;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Middleware\WindowsAuthenticate;

class WindowsAuthenticateTest extends DatabaseTestCase
{
    /** @test */
    public function middleware_authenticates_users()
    {
        $request = app('request');

        $request->server->set('AUTH_USER', 'jdoe');

        $middleware = app(WindowsAuthenticate::class);

        $user = $this->makeLdapUser([
            'cn' => 'John Doe',
            'userprincipalname' => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
        ]);

        $query = m::mock(Builder::class);

        $query
            ->shouldReceive('where')->once()->withArgs([['samaccountname' => 'jdoe']])->andReturn($query)
            ->shouldReceive('first')->once()->andReturn($user);

        Resolver::shouldReceive('query')->once()->andReturn($query)
            ->shouldReceive('getEloquentUsernameAttribute')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname')
            ->shouldReceive('byModel')->once()->andReturn(($user));

        $middleware->handle($request, function () {});

        $authenticated = auth()->user();

        $this->assertEquals($user, $authenticated->ldap);
        $this->assertEquals('John Doe', $authenticated->name);
        $this->assertEquals('jdoe@email.com', $authenticated->email);
        $this->assertNotEmpty($authenticated->remember_token);
    }

    /** @test */
    public function middleware_continues_request_when_user_is_not_found()
    {
        $request = app('request');

        $request->server->set('AUTH_USER', 'jdoe');

        $middleware = app(WindowsAuthenticate::class);

        $query = m::mock(Builder::class);

        $query
            ->shouldReceive('where')->once()->withArgs([['samaccountname' => 'jdoe']])->andReturn($query)
            ->shouldReceive('first')->once()->andReturn(null);

        Resolver::shouldReceive('query')->once()->andReturn($query);

        $middleware->handle($request, function () {});

        $this->assertNull(auth()->user());
    }
}
