<?php

namespace Adldap\Laravel\Tests;

use Adldap\Models\User;
use Mockery;
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
        // Get around E Strict warning about mockery's __call signature being different
        if(defined('E_STRICT')) error_reporting('E_ALL ^ E_STRICT');

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

        $this->assertTrue(Auth::attempt(['mail' => 'jdoe@email.com', 'password' => '12345']));

        $user = \DB::table('users')->where('id', '=', 1)->first();

        $this->assertEquals('jdoe@email.com', $user->email);
        $this->assertTrue(\Hash::check('12345', $user->password));
    }

    public function testAuthFails()
    {
        // Get around E Strict warning about mockery's __call signature being different
        if(defined('E_STRICT')) error_reporting('E_ALL ^ E_STRICT');

        $mockedBuilder = Mockery::mock('Adldap\Query\Builder');

        $mockedSearch = Mockery::mock('Adldap\Classes\Search');

        $mockedUsers = Mockery::mock('Adldap\Classes\Users');

        $mockedSearch->shouldReceive('first')->once()->andReturn(null);
        $mockedSearch->shouldReceive('whereEquals')->once()->andReturn($mockedBuilder);

        $mockedUsers->shouldReceive('search')->once()->andReturn($mockedSearch);

        Adldap::shouldReceive('users')->once()->andReturn($mockedUsers);

        $this->assertFalse(Auth::attempt(['mail' => 'jdoe@email.com', 'password' => '12345']));
    }

    public function testAuthFailsWhenUserFound()
    {
        // Get around E Strict warning about mockery's __call signature being different
        if(defined('E_STRICT')) error_reporting('E_ALL ^ E_STRICT');

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

        $this->assertFalse(Auth::attempt(['mail' => 'jdoe@email.com', 'password' => '12345']));
    }
}
