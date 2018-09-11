<?php

namespace Adldap\Laravel\Tests;

use Mockery as m;
use Adldap\Query\Builder;
use Adldap\AdldapInterface;
use Adldap\Schemas\SchemaInterface;
use Adldap\Connections\ProviderInterface;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Laravel\Resolvers\UserResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class UserResolverTest extends TestCase
{
    /** @test */
    public function eloquent_username_default()
    {
        $ldap = m::mock(AdldapInterface::class);

        $resolver = new UserResolver($ldap);

        $this->assertEquals('email', $resolver->getEloquentUsernameAttribute());
    }

    /** @test */
    public function ldap_auth_username_default()
    {
        $ldap = m::mock(AdldapInterface::class);

        $resolver = new UserResolver($ldap);

        $this->assertEquals('distinguishedname', $resolver->getLdapAuthAttribute());
    }

    /** @test */
    public function ldap_username_default()
    {
        $ldap = m::mock(AdldapInterface::class);

        $resolver = new UserResolver($ldap);

        $this->assertEquals('userprincipalname', $resolver->getLdapDiscoveryAttribute());
    }

    /** @test */
    public function by_credentials_returns_null_on_empty_credentials()
    {
        $ldap = m::mock(AdldapInterface::class);

        $resolver = new UserResolver($ldap);

        $this->assertNull($resolver->byCredentials());
    }

    /** @test */
    public function scopes_are_applied_when_query_is_called()
    {
        $schema = m::mock(SchemaInterface::class);

        $schema->shouldReceive('userPrincipalName')->once()->withNoArgs()->andReturn('userprincipalname');

        $builder = m::mock(Builder::class);

        $builder->shouldReceive('whereHas')->once()->withArgs(['userprincipalname'])
            ->shouldReceive('getSchema')->once()->andReturn($schema);

        $provider = m::mock(ProviderInterface::class);

        $provider->shouldReceive('search')->once()->andReturn($provider)
            ->shouldReceive('users')->once()->andReturn($builder);

        $ad = m::mock(AdldapInterface::class);

        $ad->shouldReceive('getProvider')->with('default')->andReturn($provider);

        $resolver = new UserResolver($ad);

        $this->assertInstanceOf(Builder::class, $resolver->query());
    }

    /** @test */
    public function connection_is_set_upon_creation()
    {
        Config::shouldReceive('get')->once()->with('adldap_auth.connection', 'default')->andReturn('other-test');

        $ad = m::mock(AdldapInterface::class);

        new UserResolver($ad);
    }

    /** @test */
    public function by_credentials_retrieves_alternate_username_attribute_depending_on_user_provider()
    {
        $query = m::mock(Builder::class);

        $query->shouldReceive('whereEquals')->once()->with('userprincipalname', 'jdoe')->andReturnSelf()
            ->shouldReceive('first')->andReturnNull();

        $ldapProvider = m::mock(ProviderInterface::class);

        $ldapProvider->shouldReceive('search')->once()->andReturnSelf()
            ->shouldReceive('users')->once()->andReturn($query);

        $ad = m::mock(AdldapInterface::class);

        $ad->shouldReceive('getProvider')->once()->andReturn($ldapProvider);

        $ad->shouldReceive('getProvider')->andReturnSelf();

        $authProvider = m::mock(NoDatabaseUserProvider::class);

        Auth::shouldReceive('guard')->once()->andReturnSelf()->shouldReceive('getProvider')->once()->andReturn($authProvider);

        Config::shouldReceive('get')->with('adldap_auth.connection', 'default')->andReturn('default')
            ->shouldReceive('get')->with('adldap_auth.usernames.ldap.discover', 'userprincipalname')->andReturn('userprincipalname')
            ->shouldReceive('get')->with('adldap_auth.scopes', [])->andReturn([]);

        $resolver = new UserResolver($ad);

        $resolver->byCredentials(['userprincipalname' => 'jdoe', 'password' => 'Password1']);
    }
}
