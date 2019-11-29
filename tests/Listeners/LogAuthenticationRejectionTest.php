<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\AuthenticationRejected;
use Adldap\Laravel\Listeners\LogAuthenticationRejection;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogAuthenticationRejectionTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogAuthenticationRejection();

        $user = m::mock(User::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new AuthenticationRejected($user);

        $logged = "User '{$name}' has failed validation. They have been denied authentication.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
