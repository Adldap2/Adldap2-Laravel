<?php

namespace Adldap\Laravel\Tests;

use Mockery;
use Adldap\Models\User;
use Adldap\Laravel\Facades\Adldap;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class AdldapTest extends FunctionalTestCase
{
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
        $this->assertEquals($adUser, $user->adldapUser);
    }

    public function testAuthFails()
    {
        $mockedBuilder = Mockery::mock('Adldap\Query\Builder');

        $mockedSearch = Mockery::mock('Adldap\Classes\Search');

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

        $mockedUsers = Mockery::mock('Adldap\Classes\Users');

        $mockedUsers->shouldReceive('search')->once()->andReturn($mockedSearch);

        Adldap::shouldReceive('users')->once()->andReturn($mockedUsers);

        $nonExistantInputKey = 'non-existant-key';

        $this->setExpectedException('ErrorException');

        Auth::attempt([$nonExistantInputKey => 'jdoe@email.com', 'password' => '12345']);
    }
}
