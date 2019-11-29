<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\Authenticated;
use Adldap\Laravel\Listeners\LogAuthenticated;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogAuthenticatedTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogAuthenticated();

        $user = m::mock(User::class);

        $user->shouldReceive('getCommonName')->andReturn('jdoe');

        $e = new Authenticated($user);

        $logged = "User 'jdoe' has successfully passed LDAP authentication.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
