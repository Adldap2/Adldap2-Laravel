<?php

namespace Adldap\Laravel\Tests;

use Adldap\Models\User;
use Adldap\AdldapInterface;
use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Tests\Scopes\JohnDoeScope;
use Adldap\Laravel\Tests\Models\User as EloquentUser;
use Adldap\Laravel\Tests\Handlers\LdapAttributeHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DatabaseProviderTest extends DatabaseTestCase
{
    /**
     * @test
     * @expectedException \Adldap\Laravel\Exceptions\ConfigurationMissingException
     */
    public function configuration_not_found_exception_when_config_is_null()
    {
        config(['adldap' => null]);

        App::make('adldap');
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
            'cn'    => 'John Doe',
            'userprincipalname'  => 'jdoe@email.com',
        ]);

        Resolver::shouldReceive('byModel')->once()->andReturn($user)
            ->shouldReceive('byCredentials')->once()->andReturn($user)
            ->shouldReceive('authenticate')->once()->andReturn(true);

        $this->assertTrue(Auth::attempt($credentials));
        $this->assertInstanceOf(EloquentUser::class, Auth::user());
        $this->assertInstanceOf(User::class, Auth::user()->ldap);
    }

    /** @test */
    public function auth_fails_when_user_found()
    {
        $user = $this->makeLdapUser([
            'cn'    => 'John Doe',
            'userprincipalname'  => 'jdoe@email.com',
        ]);

        Resolver::shouldReceive('byCredentials')->once()->andReturn($user)
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
        config(['adldap_auth.scopes' => [JohnDoeScope::class]]);

        $expectedFilter = '(&(objectclass=\70\65\72\73\6f\6e)(objectcategory=\70\65\72\73\6f\6e)(cn=\4a\6f\68\6e\20\44\6f\65))';

        $this->assertEquals($expectedFilter, Resolver::query()->getQuery());
    }

    /** @test */
    public function attribute_handlers_are_used()
    {
        $default = config('adldap_auth.sync_attributes');

        config(['adldap_auth.sync_attributes' => array_merge($default, [LdapAttributeHandler::class])]);

        $this->auth_passes();

        $user = Auth::user();

        $this->assertEquals('handled', $user->name);
    }

    /**
     * @test
     * @expectedException \Adldap\AdldapException
     */
    public function invalid_attribute_handlers_throw_exception()
    {
        // Inserting an invalid attribute handler that
        // does not contain a `handle` method.
        config(['adldap_auth.sync_attributes' => [\stdClass::class]]);

        $user = $this->makeLdapUser([
            'cn'    => 'John Doe',
            'userprincipalname'  => 'jdoe@email.com',
        ]);

        $model = new EloquentUser();

        $importer = new Import($user, $model);

        $importer->handle();
    }

    /** @test */
    public function auth_attempts_fallback_using_config_option()
    {
        config(['adldap_auth.login_fallback' => true]);

        EloquentUser::create([
            'email'    => 'jdoe@email.com',
            'name'     => 'John Doe',
            'password' => bcrypt('Password123'),
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => 'Password123',
        ];

        Resolver::shouldReceive('byCredentials')->times(3)->andReturn(null)
            ->shouldReceive('byModel')->once()->andReturn(null);

        $this->assertTrue(Auth::attempt($credentials));

        $this->assertFalse(Auth::attempt(
            array_replace($credentials, ['password' => 'Invalid'])
        ));

        config(['adldap_auth.login_fallback' => false]);

        $this->assertFalse(Auth::attempt($credentials));
    }

    /** @test */
    public function auth_attempts_using_fallback_does_not_require_connection()
    {
        config(['adldap_auth.login_fallback' => true]);

        EloquentUser::create([
            'email'    => 'jdoe@email.com',
            'name'     => 'John Doe',
            'password' => bcrypt('Password123'),
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => 'Password123',
        ];

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertInstanceOf('Adldap\Laravel\Tests\Models\User', $user);
        $this->assertEquals('jdoe@email.com', $user->email);
    }

    /** @test */
    public function passwords_are_synced_when_enabled()
    {
        config(['adldap_auth.passwords.sync' => true]);

        $credentials = [
            'email' => 'jdoe@email.com',
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
        config(['adldap_auth.passwords.sync' => false]);

        $credentials = [
            'email' => 'jdoe@email.com',
            'password' => '12345',
        ];

        $this->auth_passes($credentials);

        $user = EloquentUser::first();

        // This check will fail due to password synchronization being disabled.
        $this->assertFalse(Hash::check($credentials['password'], $user->password));
    }

    /** @test */
    public function passwords_are_not_updated_when_sync_is_disabled()
    {
        config(['adldap_auth.passwords.sync' => false]);

        $credentials = [
            'email' => 'jdoe@email.com',
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
            'adldap_auth.login_fallback' => false,
            'adldap_auth.rules' => [\Adldap\Laravel\Validation\Rules\DenyTrashed::class],
        ]);

        $credentials = [
            'email' => 'jdoe@email.com',
            'password' => '12345',
        ];

        $ldapUser = $this->makeLdapUser();

        Resolver::shouldReceive('byCredentials')->twice()->andReturn($ldapUser)
            ->shouldReceive('byModel')->once()->andReturn($ldapUser)
            ->shouldReceive('authenticate')->twice()->andReturn(true);

        $this->assertTrue(Auth::attempt($credentials));

        EloquentUser::first()->delete();

        $this->assertFalse(Auth::attempt($credentials));
    }

    /** @test */
    public function only_imported_users_are_allowed_to_authenticate_when_rule_is_applied()
    {
        config([
            'adldap_auth.login_fallback' => false,
            'adldap_auth.rules' => [\Adldap\Laravel\Validation\Rules\OnlyImported::class],
        ]);

        $credentials = [
            'email' => 'jdoe@email.com',
            'password' => '12345',
        ];

        Resolver::shouldReceive('byCredentials')->once()->andReturn($this->makeLdapUser())
            ->shouldReceive('authenticate')->once()->andReturn(true);

        $this->assertFalse(Auth::attempt($credentials));
    }

    /** @test */
    public function method_calls_are_passed_to_fallback_provider()
    {
        $this->assertEquals('Adldap\Laravel\Tests\Models\User', Auth::getProvider()->getModel());
    }
}
