<?php

namespace Adldap\Laravel\Tests;

use Mockery as m;
use Adldap\Models\User;
use Adldap\Laravel\Auth\ResolverInterface;
use Illuminate\Support\Facades\Auth;

class NoDatabaseProviderTest extends NoDatabaseTestCase
{
    public function test_auth_passes()
    {
        $credentials = [
            'email' => 'jdoe@email.com',
            'password' => '12345',
        ];

        $resolver = m::mock(ResolverInterface::class);

        $user = $this->makeLdapUser();

        $resolver
            ->shouldReceive('byCredentials')->once()->andReturn($user)
            ->shouldReceive('authenticate')->once()->withArgs([$user, $credentials])->andReturn(true);

        Auth::getProvider()->setResolver($resolver);

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($credentials['email'], $user->mail[0]);
    }
}
