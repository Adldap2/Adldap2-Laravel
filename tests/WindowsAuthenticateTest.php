<?php

namespace Adldap\Laravel\Tests;

use Mockery as m;
use Adldap\Laravel\Auth\ResolverInterface;
use Adldap\Laravel\Middleware\WindowsAuthenticate;

class WindowsAuthenticateTest extends DatabaseTestCase
{
    public function test_handle()
    {
        $request = app('request');

        $request->server->set('AUTH_USER', 'jdoe');

        $middleware = app(WindowsAuthenticate::class);

        $user = $this->makeLdapUser([
            'cn' => 'John Doe',
            'userprincipalname' => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
        ]);

        $resolver = m::mock(ResolverInterface::class);

        $resolver
            ->shouldReceive('query')->once()->andReturn($resolver)
            ->shouldReceive('where')->once()->withArgs([['samaccountname' => 'jdoe']])->andReturn($resolver)
            ->shouldReceive('firstOrFail')->once()->andReturn($user)
            ->shouldReceive('getEloquentUsername')->once()->andReturn('email')
            ->shouldReceive('getLdapUsername')->once()->andReturn('userprincipalname');

        $middleware->setResolver($resolver);

        $middleware->handle($request, function () {});

        $authenticated = auth()->user();

        $this->assertEquals('John Doe', $authenticated->name);
        $this->assertEquals('jdoe@email.com', $authenticated->email);
    }
}
