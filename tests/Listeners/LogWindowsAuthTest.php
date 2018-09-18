<?php

namespace Adldap\Laravel\Tests\Listeners;

use Mockery as m;
use Adldap\Models\User;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Laravel\Listeners\LogWindowsAuth;
use Adldap\Laravel\Events\AuthenticatedWithWindows;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;

class LogWindowsAuthTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogWindowsAuth();

        $user = m::mock(User::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new AuthenticatedWithWindows($user, m::mock(Authenticatable::class));

        $logged = "User '{$name}' has successfully authenticated via NTLM.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
