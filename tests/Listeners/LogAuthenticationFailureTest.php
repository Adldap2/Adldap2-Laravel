<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\AuthenticationFailed;
use Adldap\Laravel\Listeners\LogAuthenticationFailure;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogAuthenticationFailureTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogAuthenticationFailure();

        $user = m::mock(User::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new AuthenticationFailed($user);

        $logged = "User '{$name}' has failed LDAP authentication.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
