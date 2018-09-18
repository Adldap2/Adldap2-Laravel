<?php

namespace Adldap\Laravel\Tests\Listeners;

use Mockery as m;
use Adldap\Models\User;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Laravel\Listeners\LogDiscovery;
use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Illuminate\Support\Facades\Log;

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
