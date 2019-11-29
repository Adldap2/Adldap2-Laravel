<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\Synchronizing;
use Adldap\Laravel\Listeners\LogSynchronizing;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogSynchronizingTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogSynchronizing();

        $user = m::mock(User::class);
        $model = m::mock(TestUser::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new Synchronizing($user, $model);

        $logged = "User '{$name}' is being synchronized.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
