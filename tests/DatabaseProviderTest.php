<?php

namespace Adldap\Laravel\Tests;

use Adldap\AdldapInterface;
use Adldap\Connections\ConnectionInterface;
use Adldap\Connections\Provider;
use Adldap\Connections\ProviderInterface;
use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Tests\Handlers\LdapAttributeHandler;
use Adldap\Laravel\Tests\Models\TestUser as EloquentUser;
use Adldap\Laravel\Tests\Scopes\JohnDoeScope;
use Adldap\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mockery as m;

class DatabaseProviderTest extends DatabaseTestCase
{
    use WithFaker;

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function configuration_not_found_exception_when_config_is_null()
    {
        config(['ldap' => null]);

        App::make(AdldapInterface::class);
    }

    /** @test */
    public function adldap_is_bound_to_interface()
    {
        $adldap = App::make(AdldapInterface::class);

        $this->assertInstanceOf(AdldapInterface::class, $adldap);
    }

    /** @test */
    public function auth_passes($credentials = null)
    {
        $credentials = $credentials ?: ['email' => 'jdoe@email.com', 'password' => '12345'];

        $user = $this->makeLdapUser([
            'objectguid'            => [$this->faker->uuid],
            'cn'                    => ['John Doe'],
            'userprincipalname'     => ['jdoe@email.com'],
        ]);

        Resolver::shouldReceive('byModel')->once()->andReturn($user)
            ->shouldReceive('byCredentials')->once()->andReturn($user)
            ->shouldReceive('getDatabaseIdColumn')->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->andReturn('userprincipalname')
            ->shouldReceive('authenticate')->once()->andReturn(true);

        $this->assertTrue(Auth::attempt($credentials));
        $this->assertInstanceOf(EloquentUser::class, Auth::user());
        $this->assertInstanceOf(User::class, Auth::user()->ldap);
    }

    /** @test */
    public function auth_fails_when_user_found()
    {
        $user = $this->makeLdapUser([
            'objectguid'            => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'cn'                    => ['John Doe'],
            'userprincipalname'     => ['jdoe@email.com'],
        ]);

        Resolver::shouldReceive('byCredentials')->once()->andReturn($user)
            ->shouldReceive('getDatabaseIdColumn')->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->andReturn('userprincipalname')
            ->shouldReceive('authenticate')->once()->andReturn(false);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    /** @test */
    public function auth_fails_when_user_not_found()
    {
        Resolver::shouldReceive('byCredentials')->once()->andReturn(null);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    /** @test */
    public function config_scopes_are_applied()
    {
        $ldapMock = m::mock(AdldapInterface::class);
        App::instance(AdldapInterface::class, $ldapMock);
        /** @var Provider $provider */
        $provider = App::make(Provider::class);
        config(['ldap_auth.scopes' => [JohnDoeScope::class]]);

        $providerMock = m::mock(ProviderInterface::class);
        $connectionMock = m::mock(ConnectionInterface::class);

        $providerMock->shouldReceive('getConnection')->once()->andReturn($connectionMock);
        $connectionMock->shouldReceive('isBound')->once()->andReturn(true);
        $ldapMock->shouldReceive('getProvider')->once()->andReturn($providerMock);
        $providerMock->shouldReceive('search')->once()->andReturn($provider->search());

        $expectedFilter = '(&(objectclass=\75\73\65\72)(objectcategory=\70\65\72\73\6f\6e)(!(objectclass=\63\6f\6e\74\61\63\74))(cn=\4a\6f\68\6e\20\44\6f\65))';

        $this->assertEquals($expectedFilter, Resolver::query()->getQuery());
    }

    /** @test */
    public function attribute_handlers_are_used()
    {
        $default = config('ldap_auth.sync_attributes');

        config(['ldap_auth.sync_attributes' => array_merge($default, [LdapAttributeHandler::class])]);

        $this->auth_passes();

        $user = Auth::user();

        $this->assertEquals('handled', $user->name);
    }

    /** @test */
    public function invalid_attribute_handlers_does_not_throw_exception()
    {
        // Inserting an invalid attribute handler that
        // does not contain a `handle` method.
        config(['ldap_auth.sync_attributes' => [\stdClass::class]]);

        $user = $this->makeLdapUser([
            'objectguid'            => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'cn'                    => ['John Doe'],
            'userprincipalname'     => ['jdoe@email.com'],
        ]);

        $importer = new Import($user, new EloquentUser());

        Resolver::shouldReceive('getDatabaseIdColumn')->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->once()->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->once()->andReturn('userprincipalname');

        $this->assertInstanceOf(EloquentUser::class, $importer->handle());
    }

    /** @test */
    public function sync_attribute_as_string_will_return_null()
    {
        config([
            'ldap_auth.sync_attributes' => [
                'email' => 'userprincipalname',
                'name'  => 'cn',
            ],
        ]);

        // LDAP user does not have common name.
        $user = $this->makeLdapUser([
            'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'userprincipalname' => ['jdoe@email.com'],
        ]);

        $importer = new Import($user, new EloquentUser());

        $model = $importer->handle();

        $this->assertInstanceOf(EloquentUser::class, $model);
        $this->assertNull($model->name);
    }

    /** @test */
    public function sync_attribute_as_int_boolean_or_array_will_be_used()
    {
        config([
            'ldap_auth.sync_attributes' => [
                'email'  => 'userprincipalname',
                'string' => 'not-an-LDAP-attribute',
                'int'    => 1,
                'bool'   => true,
                'array'  => ['one', 'two'],
            ],
        ]);

        // LDAP user does not have common name.
        $user = $this->makeLdapUser([
            'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'userprincipalname' => ['jdoe@email.com'],
        ]);

        $importer = new Import($user, new EloquentUser());

        $model = $importer->handle();

        $this->assertInstanceOf(EloquentUser::class, $model);
        $this->assertNull($model->string);
        $this->assertEquals($model->int, 1);
        $this->assertEquals($model->bool, true);
        $this->assertEquals($model->array, ['one', 'two']);
    }

    /** @test */
    public function auth_attempts_fallback_using_config_option()
    {
        config(['ldap_auth.login_fallback' => true]);

        EloquentUser::create([
            'email'    => 'jdoe@email.com',
            'name'     => 'John Doe',
            'password' => Hash::make('Password123'),
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => 'Password123',
        ];

        Resolver::shouldReceive('byCredentials')->times(3)->andReturn(null)
            ->shouldReceive('byModel')->times(2)->andReturn(null);

        $this->assertTrue(Auth::attempt($credentials));

        $this->assertFalse(Auth::attempt(
            array_replace($credentials, ['password' => 'Invalid'])
        ));

        config(['ldap_auth.login_fallback' => false]);

        $this->assertFalse(Auth::attempt($credentials));
    }

    /** @test */
    public function auth_attempts_using_fallback_does_not_require_connection()
    {
        $ldapMock = m::mock(AdldapInterface::class);
        App::instance(AdldapInterface::class, $ldapMock);
        /** @var Provider $provider */
        $provider = App::make(Provider::class);
        config(['ldap_auth.login_fallback' => true]);

        EloquentUser::create([
            'email'    => 'jdoe@email.com',
            'name'     => 'John Doe',
            'password' => Hash::make('Password123'),
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => 'Password123',
        ];

        $providerMock = m::mock(ProviderInterface::class);
        $connectionMock = m::mock(ConnectionInterface::class);

        $providerMock->shouldReceive('getConnection')->times(3)->andReturn($connectionMock);
        $connectionMock->shouldReceive('isBound')->times(3)->andReturn(true);
        $ldapMock->shouldReceive('getProvider')->times(3)->andReturn($providerMock);
        $providerMock->shouldReceive('search')->times(3)->andReturn($provider->search());

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertInstanceOf('Adldap\Laravel\Tests\Models\TestUser', $user);
        $this->assertEquals('jdoe@email.com', $user->email);
    }

    /** @test */
    public function passwords_are_synced_when_enabled()
    {
        config(['ldap_auth.passwords.sync' => true]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => '12345',
        ];

        $this->auth_passes($credentials);

        $user = EloquentUser::first();

        // This check will pass due to password synchronization being enabled.
        $this->assertTrue(Hash::check($credentials['password'], $user->password));
    }

    /** @test */
    public function passwords_are_not_synced_when_sync_is_disabled()
    {
        config(['ldap_auth.passwords.sync' => false]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => '12345',
        ];

        $this->auth_passes($credentials);

        $user = EloquentUser::first();

        // This check will fail due to password synchronization being disabled.
        $this->assertFalse(Hash::check($credentials['password'], $user->password));
    }

    /** @test */
    public function users_without_a_guid_are_synchronized_properly()
    {
        EloquentUser::create([
            'email'    => 'jdoe@email.com',
            'name'     => 'John Doe',
            'password' => Hash::make('Password123'),
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => 'Password123',
        ];

        $ldapUser = $this->makeLdapUser();

        Resolver::shouldReceive('byCredentials')->once()->andReturn($ldapUser)
            ->shouldReceive('getDatabaseIdColumn')->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->andReturn('userprincipalname')
            ->shouldReceive('byModel')->once()->andReturn($ldapUser)
            ->shouldReceive('authenticate')->once()->andReturn(true);

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertInstanceOf('Adldap\Laravel\Tests\Models\TestUser', $user);
        $this->assertEquals($user->objectguid, $ldapUser->getConvertedGuid());
        $this->assertEquals('jdoe@email.com', $user->email);
        $this->assertEquals(1, EloquentUser::count());
    }

    /** @test */
    public function users_without_a_guid_and_a_changed_username_have_new_record_created()
    {
        // Create an existing synchronized user.
        EloquentUser::create([
            'email'    => 'jdoe@email.com',
            'name'     => 'John Doe',
            'password' => Hash::make('Password123'),
        ]);

        $credentials = [
            'email'    => 'johndoe@email.com',
            'password' => 'Password123',
        ];

        // Generate an LDAP user with a changed UPN and Mail.
        $ldapUser = $this->makeLdapUser([
            'objectguid'        => ['cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0'],
            'samaccountname'    => ['jdoe'],
            'userprincipalname' => ['johndoe@email.com'],
            'mail'              => ['johndoe@email.com'],
            'cn'                => ['John Doe'],
        ]);

        Resolver::shouldReceive('byCredentials')->once()->andReturn($ldapUser)
            ->shouldReceive('getDatabaseIdColumn')->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->andReturn('userprincipalname')
            ->shouldReceive('byModel')->once()->andReturn($ldapUser)
            ->shouldReceive('authenticate')->once()->andReturn(true);

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertInstanceOf('Adldap\Laravel\Tests\Models\TestUser', $user);
        $this->assertEquals($user->objectguid, $ldapUser->getConvertedGuid());
        $this->assertEquals('johndoe@email.com', $user->email);
        $this->assertEquals(2, EloquentUser::count());
    }

    /** @test */
    public function passwords_are_not_updated_when_sync_is_disabled()
    {
        config(['ldap_auth.passwords.sync' => false]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => '12345',
        ];

        $this->auth_passes($credentials);

        $user = EloquentUser::first();

        $this->auth_passes($credentials);

        $this->assertEquals($user->password, $user->fresh()->password);
    }

    /** @test */
    public function trashed_rule_prevents_deleted_users_from_logging_in()
    {
        config([
            'ldap_auth.login_fallback' => false,
            'ldap_auth.rules'          => [\Adldap\Laravel\Validation\Rules\DenyTrashed::class],
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => '12345',
        ];

        $user = $this->makeLdapUser();

        Resolver::shouldReceive('byCredentials')->twice()->andReturn($user)
            ->shouldReceive('getDatabaseIdColumn')->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->andReturn('userprincipalname')
            ->shouldReceive('byModel')->once()->andReturn($user)
            ->shouldReceive('authenticate')->twice()->andReturn(true);

        $this->assertTrue(Auth::attempt($credentials));

        EloquentUser::first()->delete();

        $this->assertFalse(Auth::attempt($credentials));
    }

    /** @test */
    public function only_imported_users_are_allowed_to_authenticate_when_rule_is_applied()
    {
        config([
            'ldap_auth.login_fallback' => false,
            'ldap_auth.rules'          => [\Adldap\Laravel\Validation\Rules\OnlyImported::class],
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => '12345',
        ];

        $user = $this->makeLdapUser();

        Resolver::shouldReceive('byCredentials')->once()->andReturn($user)
            ->shouldReceive('getDatabaseIdColumn')->andReturn('objectguid')
            ->shouldReceive('getDatabaseUsernameColumn')->andReturn('email')
            ->shouldReceive('getLdapDiscoveryAttribute')->andReturn('userprincipalname')
            ->shouldReceive('authenticate')->once()->andReturn(true);

        $this->assertFalse(Auth::attempt($credentials));
    }

    /** @test */
    public function method_calls_are_passed_to_fallback_provider()
    {
        $this->assertEquals('Adldap\Laravel\Tests\Models\TestUser', Auth::getProvider()->getModel());
    }
}
