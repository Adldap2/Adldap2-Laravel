<?php

namespace Adldap\Laravel\Tests;

use Adldap\Models\User;
use Adldap\AdldapInterface;
use Adldap\Laravel\Tests\Models\User as EloquentUser;
use Adldap\Laravel\Tests\Handlers\LdapAttributeHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

class DatabaseProviderTest extends DatabaseTestCase
{
    public function test_configuration_not_found_exception()
    {
        $this->app['config']->set('adldap', null);

        $this->setExpectedException('Adldap\Laravel\Exceptions\ConfigurationMissingException');

        App::make('adldap');
    }

    public function test_registration()
    {
        $this->assertInstanceOf(\Adldap\Laravel\AdldapServiceProvider::class, app()->register(\Adldap\Laravel\AdldapServiceProvider::class));
        $this->assertInstanceOf(\Adldap\Laravel\AdldapAuthServiceProvider::class, app()->register(\Adldap\Laravel\AdldapAuthServiceProvider::class));
    }

    public function test_contract_resolve()
    {
        $adldap = $this->app->make(AdldapInterface::class);

        $this->assertInstanceOf(AdldapInterface::class, $adldap);
    }

    public function test_auth_passes($credentials = null)
    {
        $credentials = $credentials ?: ['email' => 'jdoe@email.com', 'password' => '12345'];

        $user = $this->getMockUser([
            'cn'             => 'John Doe',
            'mail'           => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
        ]);

        $connection = $this->getMockConnection();

        $connection->method('isBound')->willReturn(true);
        $connection->method('search')->willReturn('resource');
        $connection->method('getEntries')->willReturn([
            'count' => 1,
            $user->getAttributes(),
        ]);

        $connection->expects($this->exactly(2))->method('bind')
            ->with($this->logicalOr(
                $this->equalTo('jdoe'),
                $this->equalTo('admin')
            ))
            ->willReturn(true);

        // Due to Laravel firing service provider registrations, the
        // two events we expect to be fired need to be
        // added on to this total.
        Event::shouldReceive('dispatch')->atLeast()->times(3)->withAnyArgs();

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertEquals($credentials['email'], $user->email);
        $this->assertTrue(Hash::check($credentials['password'], $user->password));
        $this->assertInstanceOf(User::class, $user->ldap);
    }

    public function test_auth_fails_when_user_found()
    {
        $user = $this->getMockUser([
            'cn'             => '',
            'mail'           => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
        ]);

        $connection = $this->getMockConnection(['getLastError', 'errNo']);

        $connection->expects($this->exactly(1))->method('isBound')->willReturn(true);
        $connection->expects($this->exactly(1))->method('search')->willReturn('resource');
        $connection->expects($this->exactly(1))->method('getEntries')->willReturn([
            'count' => 1,
            $user->getAttributes(),
        ]);

        $connection->expects($this->exactly(1))->method('getLastError')->willReturn('Bind Failure.');
        $connection->expects($this->exactly(1))->method('errNo')->willReturn(1);
        $connection->expects($this->exactly(1))->method('bind')
            ->with($this->equalTo('jdoe'))
            ->willReturn(false);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    public function test_auth_fails_when_user_not_found()
    {
        $connection = $this->getMockConnection();

        $connection->expects($this->exactly(1))->method('isBound')->willReturn(true);
        $connection->expects($this->exactly(1))->method('search')->willReturn('resource');
        $connection->expects($this->exactly(1))->method('getEntries')->willReturn([
            'count' => 0,
        ]);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    public function test_config_limitation_filter()
    {
        $filter = '(cn=John Doe)';

        $expectedFilter = '(&(cn=John Doe)(objectclass=\70\65\72\73\6f\6e)(objectcategory=\70\65\72\73\6f\6e)(mail=\6a\64\6f\65\40\65\6d\61\69\6c\2e\63\6f\6d))';

        $this->app['config']->set('adldap_auth.limitation_filter', $filter);

        $user = $this->getMockUser([
            'cn'             => '',
            'mail'           => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
        ]);

        $connection = $this->getMockConnection();

        $connection->method('isBound')->willReturn(true);

        $connection->method('search')->with(
            $this->equalTo(''),
            $this->equalTo($expectedFilter),
            $this->equalTo([])
        )->willReturn('resource');

        $connection->method('getEntries')->willReturn([
            'count' => 1,
            $user->getAttributes(),
        ]);

        $connection->expects($this->exactly(2))->method('bind')
            ->with($this->logicalOr(
                $this->equalTo('jdoe'),
                $this->equalTo('admin')
            ))
            ->willReturn(true);

        $this->assertTrue(Auth::attempt(['email' => 'jdoe@email.com', 'password' => 'password']));
    }

    public function test_config_callback_attribute_handler()
    {
        $this->app['config']->set('adldap_auth.sync_attributes', [
            LdapAttributeHandler::class,
        ]);

        $this->test_auth_passes();

        $user = Auth::user();

        $this->assertEquals('handled', $user->name);
    }

    /**
     * @expectedException \Adldap\AdldapException
     */
    public function test_config_invalid_config_attribute_handler()
    {
        $this->app['config']->set('adldap_auth.sync_attributes', [
            \stdClass::class,
        ]);

        $credentials = ['email' => 'jdoe@email.com', 'password' => '12345'];

        $user = $this->getMockUser([
            'cn'             => 'John Doe',
            'mail'           => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
        ]);

        $connection = $this->getMockConnection();

        $connection->method('isBound')->willReturn(true);
        $connection->method('search')->willReturn('resource');
        $connection->method('getEntries')->willReturn([
            'count' => 1,
            $user->getAttributes(),
        ]);

        Auth::attempt($credentials);
    }

    public function test_config_login_fallback()
    {
        $this->app['config']->set('adldap_auth.login_fallback', true);

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

        $this->app['config']->set('adldap_auth.login_fallback', false);

        $this->assertFalse(Auth::attempt($credentials));
    }

    public function test_config_login_fallback_no_connection()
    {
        $this->app['config']->set('adldap_auth.login_fallback', true);

        $connection = $this->getMockConnection();

        $connection->expects($this->exactly(1))->method('isBound')->willReturn(false);

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

    public function test_config_password_sync_enabled()
    {
        $this->app['config']->set('adldap_auth.password_sync', true);

        $email = 'jdoe@email.com';
        $password = '12345';

        $this->test_auth_passes(compact('email', 'password'));

        $user = EloquentUser::first();

        $this->assertInstanceOf(EloquentUser::class, $user);

        // This check will pass due to password synchronization being enabled.
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_config_password_sync_disabled()
    {
        $this->app['config']->set('adldap_auth.password_sync', false);

        $user = $this->getMockUser([
            'cn'             => '',
            'mail'           => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
        ]);

        $connection = $this->getMockConnection();

        $connection->method('isBound')->willReturn(true);
        $connection->method('search')->willReturn('resource');
        $connection->method('getEntries')->willReturn([
            'count' => 1,
            $user->getAttributes(),
        ]);

        $connection->expects($this->exactly(2))->method('bind')
            ->with($this->logicalOr(
                $this->equalTo('jdoe'),
                $this->equalTo('admin')
            ))
            ->willReturn(true);

        $email = 'jdoe@email.com';
        $password = '12345';

        $this->assertTrue(Auth::attempt(compact('email', 'password')));

        $user = Auth::user();

        // This check will fail due to password synchronization being disabled.
        $this->assertFalse(Hash::check($password, $user->password));
    }
}
