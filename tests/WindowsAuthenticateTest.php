<?php

namespace Adldap\Laravel\Tests;

use Mockery as m;
use Adldap\Query\Builder;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Laravel\Validation\Rules\DenyTrashed;
use Adldap\Laravel\Middleware\WindowsAuthenticate;

class WindowsAuthenticateTest extends DatabaseTestCase
{
    /** @test */
    public function middleware_authenticates_users()
    {
        $request = app('request');

        $request->server->set('AUTH_USER', 'jdoe');

        $user = $this->makeLdapUser([
            'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'cn'                => ['John Doe'],
            'userprincipalname' => ['jdoe@email.com'],
            'samaccountname'    => ['jdoe'],
        ]);

        $query = m::mock(Builder::class);

        $query
            ->shouldReceive('whereEquals')->once()->withArgs(['samaccountname', 'jdoe'])->andReturn($query)
            ->shouldReceive('first')->once()->andReturn($user);

        Resolver::shouldReceive('query')->once()->andReturn($query)
            ->shouldReceive('getDatabaseIdColumn')->twice()->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname')
            ->shouldReceive('byModel')->once()->andReturn($user);

        app(WindowsAuthenticate::class)->handle($request, function () {
        });

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

        $query = m::mock(Builder::class);

        $query
            ->shouldReceive('whereEquals')->once()->withArgs(['samaccountname', 'jdoe'])->andReturn($query)
            ->shouldReceive('first')->once()->andReturn(null);

        Resolver::shouldReceive('query')->once()->andReturn($query);

        app(WindowsAuthenticate::class)->handle($request, function () {
        });

        $this->assertNull(auth()->user());
    }

    /** @test */
    public function middleware_validates_authenticating_users()
    {
        // Deny deleted users from authenticating.
        config()->set('ldap_auth.rules', [DenyTrashed::class]);

        // Create the deleted user.
        tap(new TestUser(), function ($user) {
            $user->name = 'John Doe';
            $user->email = 'jdoe@email.com';
            $user->password = 'secret';
            $user->deleted_at = now();

            $user->save();
        });

        $request = app('request');

        $request->server->set('AUTH_USER', 'jdoe');

        $user = $this->makeLdapUser([
            'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'cn'                => ['John Doe'],
            'userprincipalname' => ['jdoe@email.com'],
            'samaccountname'    => ['jdoe'],
        ]);

        $query = m::mock(Builder::class);

        $query
            ->shouldReceive('whereEquals')->once()->withArgs(['samaccountname', 'jdoe'])->andReturn($query)
            ->shouldReceive('first')->once()->andReturn($user);

        Resolver::shouldReceive('query')->once()->andReturn($query)
            ->shouldReceive('getDatabaseIdColumn')->twice()->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname');

        app(WindowsAuthenticate::class)->handle($request, function () {
        });

        $this->assertNull(auth()->user());
    }
}
