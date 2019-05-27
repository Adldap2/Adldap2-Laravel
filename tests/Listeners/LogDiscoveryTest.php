<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Adldap\Laravel\Listeners\LogDiscovery;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogDiscoveryTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogDiscovery();

        $user = m::mock(User::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new DiscoveredWithCredentials($user);

        $logged = "User '{$name}' has been successfully found for authentication.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
