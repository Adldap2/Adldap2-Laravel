<?php

namespace Adldap\Laravel\Tests;

use Adldap\Auth\Guard;
use Adldap\Connections\Manager;
use Adldap\Connections\Provider;
use Adldap\Contracts\AdldapInterface;
use Adldap\Laravel\Facades\Adldap;
use Adldap\Laravel\Tests\Models\User as EloquentUser;
use Adldap\Models\User;
use Adldap\Query\Builder;
use Adldap\Schemas\Schema;
use Adldap\Search\Factory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AdldapTest extends FunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Set auth configuration for email use since stock
        // laravel only comes with an email field
        $this->app['config']->set('adldap_auth.username_attribute', [
            'email' => 'mail',
        ]);

        // Set auto_connect to false.
        $this->app['config']->set('adldap.connections.default.auto_connect', false);
    }

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

    public function test_auth_passes()
    {
        $mockedProvider = $this->mock(Provider::class);
        $mockedBuilder = $this->mock(Builder::class);
        $mockedSearch = $this->mock(Factory::class);
        $mockedAuth = $this->mock(Guard::class);

        $mockedBuilder->shouldReceive('getSchema')->once()->andReturn(Schema::get());

        $adUser = (new User([], $mockedBuilder))->setRawAttributes([
            'samaccountname' => ['jdoe'],
            'mail'           => ['jdoe@email.com'],
            'cn'             => ['John Doe'],
        ]);

        $manager = new Manager();

        $manager->add('default', $mockedProvider);

        Adldap::shouldReceive('getManager')->andReturn($manager);

        $mockedProvider->shouldReceive('search')->once()->andReturn($mockedSearch);
        $mockedProvider->shouldReceive('getSchema')->andReturn(Schema::get());
        $mockedProvider->shouldReceive('auth')->once()->andReturn($mockedAuth);

        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedBuilder);
        $mockedBuilder->shouldReceive('whereEquals')->once()->andReturn($mockedBuilder);
        $mockedBuilder->shouldReceive('first')->once()->andReturn($adUser);
        $mockedAuth->shouldReceive('attempt')->once()->andReturn(true);

        $this->assertTrue(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));

        $user = Auth::user();

        $this->assertEquals('jdoe@email.com', $user->email);
        $this->assertTrue(\Hash::check('12345', $user->password));
    }

    public function test_auth_passes_with_persistent_adldap_user()
    {
        $this->test_auth_passes();

        $this->assertInstanceOf(User::class, \Auth::user()->adldapUser);
        $this->assertInstanceOf(User::class, auth()->user()->adldapUser);
    }

    public function test_auth_passes_without_persistent_adldap_user()
    {
        $this->app['config']->set('adldap_auth.bind_user_to_model', false);

        $this->test_auth_passes();

        $this->assertNull(\Auth::user()->adldapUser);
        $this->assertNull(auth()->user()->adldapUser);
    }

    public function test_auth_fails()
    {
        $mockedProvider = $this->mock(Provider::class);
        $mockedBuilder = $this->mock(Builder::class);
        $mockedSearch = $this->mock(Factory::class);
        $mockedAuth = $this->mock(Guard::class);

        $mockedBuilder->shouldReceive('getSchema')->once()->andReturn(Schema::get());

        $adUser = (new User([], $mockedBuilder))->setRawAttributes([
            'samaccountname' => ['jdoe'],
            'mail'           => ['jdoe@email.com'],
            'cn'             => ['John Doe'],
        ]);

        $manager = new Manager();

        $manager->add('default', $mockedProvider);

        Adldap::shouldReceive('getManager')->andReturn($manager);

        $mockedProvider->shouldReceive('search')->once()->andReturn($mockedSearch);
        $mockedProvider->shouldReceive('getSchema')->andReturn(Schema::get());
        $mockedProvider->shouldReceive('auth')->once()->andReturn($mockedAuth);

        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedBuilder);
        $mockedBuilder->shouldReceive('whereEquals')->once()->andReturn($mockedBuilder);
        $mockedBuilder->shouldReceive('first')->once()->andReturn($adUser);
        $mockedAuth->shouldReceive('attempt')->once()->andReturn(false);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    public function test_auth_fails_when_user_not_found()
    {
        $mockedProvider = $this->mock(Provider::class);
        $mockedBuilder = $this->mock(Builder::class);
        $mockedSearch = $this->mock(Factory::class);

        $manager = new Manager();

        $manager->add('default', $mockedProvider);

        Adldap::shouldReceive('getManager')->andReturn($manager);

        $mockedProvider->shouldReceive('search')->once()->andReturn($mockedSearch);
        $mockedProvider->shouldReceive('getSchema')->andReturn(Schema::get());

        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedBuilder);
        $mockedBuilder->shouldReceive('whereEquals')->once()->andReturn($mockedBuilder);
        $mockedBuilder->shouldReceive('first')->once()->andReturn(null);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    public function test_credentials_key_does_not_exist()
    {
        $mockedProvider = $this->mock(Provider::class);
        $mockedSearch = $this->mock(Factory::class);
        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedSearch);

        $manager = new Manager();

        $manager->add('default', $mockedProvider);

        Adldap::shouldReceive('getManager')->andReturn($manager);

        $mockedProvider->shouldReceive('search')->once()->andReturn($mockedSearch);
        $mockedProvider->shouldReceive('getSchema')->andReturn(Schema::get());

        $nonExistantInputKey = 'non-existent-key';

        $this->setExpectedException('ErrorException');

        Auth::attempt([$nonExistantInputKey => 'jdoe@email.com', 'password' => '12345']);
    }

    public function test_config_callback_attribute_handler()
    {
        $this->app['config']->set('adldap_auth.sync_attributes', [
            'name' => 'Adldap\Laravel\Tests\Handlers\LdapAttributeHandler@name',
        ]);

        $this->test_auth_passes();

        $user = \Auth::user();

        $this->assertEquals('handled', $user->name);
    }

    public function test_config_login_fallback()
    {
        $this->app['config']->set('adldap_auth.login_fallback', true);

        $mockedProvider = $this->mock(Provider::class);
        $mockedSearch = $this->mock(Factory::class);
        $mockedSearch->shouldReceive('select')->andReturn($mockedSearch);
        $mockedSearch->shouldReceive('whereEquals')->andReturn($mockedSearch);
        $mockedSearch->shouldReceive('first')->andReturn(null);

        $manager = new Manager();

        $manager->add('default', $mockedProvider);
        $mockedProvider->shouldReceive('search')->andReturn($mockedSearch);
        $mockedProvider->shouldReceive('getSchema')->andReturn(Schema::get());

        Adldap::shouldReceive('getManager')->andReturn($manager);

        EloquentUser::create([
            'email'    => 'jdoe@email.com',
            'name'     => 'John Doe',
            'password' => bcrypt('Password123'),
        ]);

        $credentials = [
            'email'    => 'jdoe@email.com',
            'password' => 'Password123',
        ];

        $outcome = Auth::attempt($credentials);

        $user = \Auth::user();

        $this->assertTrue($outcome);
        $this->assertInstanceOf('Adldap\Laravel\Tests\Models\User', $user);
        $this->assertEquals('jdoe@email.com', $user->email);

        $this->app['config']->set('adldap_auth.login_fallback', false);

        $outcome = Auth::attempt($credentials);

        $this->assertFalse($outcome);
    }
}
