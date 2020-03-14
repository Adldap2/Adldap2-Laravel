<?php

namespace Adldap\Laravel\Tests;

use Adldap\AdldapInterface;
use Adldap\Connections\ConnectionInterface;
use Adldap\Connections\ProviderInterface;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Laravel\Resolvers\UserResolver;
use Adldap\Laravel\Scopes\UpnScope;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Query\Builder;
use Adldap\Schemas\SchemaInterface;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mockery as m;

class UserResolverTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function eloquent_username_default()
    {
        $ldap = m::mock(AdldapInterface::class);

        $resolver = new UserResolver($ldap);

        $this->assertEquals('email', $resolver->getDatabaseUsernameColumn());
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
        config(['ldap_auth.scopes' => [UpnScope::class]]);

        $schema = m::mock(SchemaInterface::class);

        $schema
            ->shouldReceive('userPrincipalName')->once()->withNoArgs()->andReturn('userprincipalname')
            ->shouldReceive('objectGuid')->once()->withNoArgs()->andReturn('objectguid');

        $builder = m::mock(Builder::class);

        $builder
            ->shouldReceive('whereHas')->once()->withArgs(['userprincipalname'])->andReturnSelf()
            ->shouldReceive('getSelects')->once()->andReturn(['*'])
            ->shouldReceive('select')->with(['*', 'objectguid'])->andReturnSelf()
            ->shouldReceive('getSchema')->twice()->andReturn($schema);

        $provider = m::mock(ProviderInterface::class);

        $provider
            ->shouldReceive('search')->once()->andReturn($provider)
            ->shouldReceive('users')->once()->andReturn($builder);

        $ad = m::mock(AdldapInterface::class);
        $ldapConnection = m::mock(ConnectionInterface::class);
        $ldapConnection->shouldReceive('isBound')->once()->andReturn(false);

        $provider->shouldReceive('getConnection')->once()->andReturn($ldapConnection);
        $provider->shouldReceive('connect')->once();

        $ad->shouldReceive('getProvider')->with('default')->andReturn($provider);

        $resolver = new UserResolver($ad);

        $this->assertInstanceOf(Builder::class, $resolver->query());
    }

    /** @test */
    public function connection_is_set_when_retrieving_provider()
    {
        Config::shouldReceive('get')->once()->with('ldap_auth.connection', 'default')->andReturn('other-domain');

        $ad = m::mock(AdldapInterface::class);
        $provider = m::mock(ProviderInterface::class);

        $ad->shouldReceive('getProvider')->with('other-domain')->andReturn($provider);
        $ldapConnection = m::mock(ConnectionInterface::class);
        $ldapConnection->shouldReceive('isBound')->once()->andReturn(false);

        $provider->shouldReceive('getConnection')->once()->andReturn($ldapConnection);
        $provider->shouldReceive('connect')->once();

        $r = m::mock(UserResolver::class, [$ad])->makePartial();

        $r->getLdapAuthProvider();
    }

    /** @test */
    public function by_credentials_retrieves_alternate_username_attribute_depending_on_user_provider()
    {
        $schema = m::mock(SchemaInterface::class);

        $schema->shouldReceive('objectGuid')->once()->withNoArgs()->andReturn('objectguid');

        $query = m::mock(Builder::class);

        $query
            ->shouldReceive('whereEquals')->once()->with('userprincipalname', 'jdoe')->andReturnSelf()
            ->shouldReceive('getSelects')->once()->andReturn(['*'])
            ->shouldReceive('select')->with(['*', 'objectguid'])->andReturnSelf()
            ->shouldReceive('getSchema')->once()->andReturn($schema)
            ->shouldReceive('first')->andReturnNull();

        $ldapProvider = m::mock(ProviderInterface::class);

        $ldapProvider
            ->shouldReceive('search')->once()->andReturnSelf()
            ->shouldReceive('users')->once()->andReturn($query);

        $ad = m::mock(AdldapInterface::class);
        $ldapConnection = m::mock(ConnectionInterface::class);
        $ldapConnection->shouldReceive('isBound')->once()->andReturn(false);

        $ldapProvider->shouldReceive('getConnection')->once()->andReturn($ldapConnection);
        $ldapProvider->shouldReceive('connect')->once();

        $ad->shouldReceive('getProvider')->once()->andReturn($ldapProvider);

        $ad->shouldReceive('getProvider')->andReturnSelf();

        $authProvider = m::mock(NoDatabaseUserProvider::class);

        Auth::shouldReceive('guard')->once()->andReturnSelf()->shouldReceive('getProvider')->once()->andReturn($authProvider);

        Config::shouldReceive('get')->with('ldap_auth.connection', 'default')->andReturn('default')
            ->shouldReceive('get')->with('ldap_auth.identifiers.ldap.locate_users_by', 'userprincipalname')->andReturn('userprincipalname')
            ->shouldReceive('get')->with('ldap_auth.scopes', [])->andReturn([]);

        $resolver = new UserResolver($ad);

        $resolver->byCredentials([
            'userprincipalname' => 'jdoe',
            'password'          => 'Password1',
        ]);
    }

    /** @test */
    public function by_id_retrieves_user_by_object_guid()
    {
        $user = $this->makeLdapUser();

        $guid = $this->faker->uuid;

        $query = m::mock(Builder::class);

        $query->shouldReceive('findByGuid')->once()->with($guid)->andReturn($user);

        $r = m::mock(UserResolver::class)->makePartial();

        $r->shouldReceive('query')->andReturn($query);

        $this->assertEquals($user, $r->byId($guid));
    }

    /** @test */
    public function by_model_retrieves_user_by_models_object_guid()
    {
        $model = new TestUser([
            'objectguid' => $this->faker->uuid,
        ]);

        $user = $this->makeLdapUser();

        $query = m::mock(Builder::class);

        $query->shouldReceive('findByGuid')->once()->with($model->objectguid)->andReturn($user);

        $r = m::mock(UserResolver::class)->makePartial();

        $r->shouldReceive('query')->andReturn($query);

        $this->assertEquals($user, $r->byModel($model));
    }
}
