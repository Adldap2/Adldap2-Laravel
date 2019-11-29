<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\Authenticating;
use Adldap\Laravel\Listeners\LogAuthentication;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogAuthenticationTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogAuthentication();

        $user = m::mock(User::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $username = 'jdoe';
        $prefix = 'prefix.';
        $suffix = '.suffix';

        $authUsername = $prefix.$username.$suffix;

        $e = new Authenticating($user, $username);

        $logged = "User '{$name}' is authenticating with username: '{$authUsername}'";

        Log::shouldReceive('info')->once()->with($logged);

        Config::shouldReceive('get')->with('ldap_auth.connection')->andReturn('default')
            ->shouldReceive('get')->with('ldap.connections.default.settings.account_prefix')->andReturn($prefix)
            ->shouldReceive('get')->with('ldap.connections.default.settings.account_suffix')->andReturn($suffix);

        $l->handle($e);
    }
}
