<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\Synchronized;
use Adldap\Laravel\Listeners\LogSynchronized;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogSynchronizedTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogSynchronized();

        $user = m::mock(User::class);
        $model = m::mock(TestUser::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new Synchronized($user, $model);

        $logged = "User '{$name}' has been successfully synchronized.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
