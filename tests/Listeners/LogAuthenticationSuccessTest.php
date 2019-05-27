<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\AuthenticationSuccessful;
use Adldap\Laravel\Listeners\LogAuthenticationSuccess;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogAuthenticationSuccessTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogAuthenticationSuccess();

        $user = m::mock(User::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new AuthenticationSuccessful($user);

        $logged = "User '{$name}' has been successfully logged in.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
