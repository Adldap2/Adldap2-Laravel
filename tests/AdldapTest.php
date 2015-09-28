<?php

namespace Adldap\Laravel\Tests;

use Mockery;
use Adldap\Models\User;
use Adldap\Laravel\Facades\Adldap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

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
    }

    public function testConfigurationNotFoundException()
    {
        $this->app['config']->set('adldap', null);

        $this->setExpectedException('Adldap\Laravel\Exceptions\ConfigurationMissingException');

        App::make('adldap');
    }

    public function testRegistration()
    {
        $this->assertTrue(app()->register('Adldap\Laravel\AdldapServiceProvider'));
        $this->assertTrue(app()->register('Adldap\Laravel\AdldapAuthServiceProvider'));
    }

    public function testContractResolve()
    {
        $this->app['config']->set('adldap.auto_connect', false);

        $adldap = $this->app->make('Adldap\Contracts\Adldap');

        $this->assertInstanceOf('Adldap\Adldap', $adldap);
        $this->assertInstanceOf('Adldap\Contracts\Adldap', $adldap);
    }

    public function testAuthPasses()
    {
        $mockedBuilder = Mockery::mock('Adldap\Query\Builder');

        $rawAttributes = [
            'samaccountname' => ['jdoe'],
            'mail' => ['jdoe@email.com'],
            'cn' => ['John Doe'],
        ];

        $adUser = (new User([], $mockedBuilder))->setRawAttributes($rawAttributes);

        $mockedSearch = Mockery::mock('Adldap\Classes\Search');
        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedSearch);

        $mockedUsers = Mockery::mock('Adldap\Classes\Users');

        $mockedSearch->shouldReceive('first')->once()->andReturn($adUser);
        $mockedSearch->shouldReceive('whereEquals')->once()->andReturn($mockedBuilder);

        $mockedUsers->shouldReceive('search')->once()->andReturn($mockedSearch);

        Adldap::shouldReceive('users')->once()->andReturn($mockedUsers);
        Adldap::shouldReceive('authenticate')->once()->andReturn(true);

        $this->assertTrue(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));

        $user = Auth::user();

        $this->assertEquals('jdoe@email.com', $user->email);
        $this->assertTrue(\Hash::check('12345', $user->password));
    }

    public function testAuthPassesWithPersistentAdldapUser()
    {
        $this->testAuthPasses();

        $this->assertInstanceOf('Adldap\Models\User', \Auth::user()->adldapUser);
        $this->assertInstanceOf('Adldap\Models\User', auth()->user()->adldapUser);
    }

    public function testAuthPassesWithoutPersistentAdldapUser()
    {
        $this->app['config']->set('adldap_auth.bind_user_to_model', false);

        $this->testAuthPasses();

        $this->assertNull(\Auth::user()->adldapUser);
        $this->assertNull(auth()->user()->adldapUser);
    }

    public function testAuthFails()
    {
        $mockedBuilder = Mockery::mock('Adldap\Query\Builder');

        $mockedSearch = Mockery::mock('Adldap\Classes\Search');
        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedSearch);

        $mockedUsers = Mockery::mock('Adldap\Classes\Users');

        $mockedSearch->shouldReceive('first')->once()->andReturn(null);
        $mockedSearch->shouldReceive('whereEquals')->once()->andReturn($mockedBuilder);

        $mockedUsers->shouldReceive('search')->once()->andReturn($mockedSearch);

        Adldap::shouldReceive('users')->once()->andReturn($mockedUsers);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    public function testAuthFailsWhenUserFound()
    {
        $mockedBuilder = Mockery::mock('Adldap\Query\Builder');

        $rawAttributes = [
            'samaccountname' => ['jdoe'],
            'mail' => ['jdoe@email.com'],
            'cn' => ['John Doe'],
        ];

        $adUser = (new User([], $mockedBuilder))->setRawAttributes($rawAttributes);

        $mockedSearch = Mockery::mock('Adldap\Classes\Search');
        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedSearch);

        $mockedUsers = Mockery::mock('Adldap\Classes\Users');

        $mockedSearch->shouldReceive('first')->once()->andReturn($adUser);
        $mockedSearch->shouldReceive('whereEquals')->once()->andReturn($mockedBuilder);

        $mockedUsers->shouldReceive('search')->once()->andReturn($mockedSearch);

        Adldap::shouldReceive('users')->once()->andReturn($mockedUsers);
        Adldap::shouldReceive('authenticate')->once()->andReturn(false);

        $this->assertFalse(Auth::attempt(['email' => 'jdoe@email.com', 'password' => '12345']));
    }

    public function testCredentialsKeyDoesNotExist()
    {
        $mockedSearch = Mockery::mock('Adldap\Classes\Search');
        $mockedSearch->shouldReceive('select')->once()->andReturn($mockedSearch);

        $mockedUsers = Mockery::mock('Adldap\Classes\Users');

        $mockedUsers->shouldReceive('search')->once()->andReturn($mockedSearch);

        Adldap::shouldReceive('users')->once()->andReturn($mockedUsers);

        $nonExistantInputKey = 'non-existant-key';

        $this->setExpectedException('ErrorException');

        Auth::attempt([$nonExistantInputKey => 'jdoe@email.com', 'password' => '12345']);
    }
}
