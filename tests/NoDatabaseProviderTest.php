<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Facades\Resolver;
use Adldap\Models\User;
use Illuminate\Support\Facades\Auth;

class NoDatabaseProviderTest extends NoDatabaseTestCase
{
    /** @test */
    public function only_ldap_is_used_when_authenticating()
    {
        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => '12345',
        ];

        $user = $this->makeLdapUser();

        Resolver::shouldReceive('byCredentials')->once()->andReturn($user)
            ->shouldReceive('authenticate')->once()->withArgs([$user, $credentials])->andReturn(true);

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($credentials['email'], $user->mail[0]);
    }
}
