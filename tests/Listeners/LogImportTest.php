<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\Importing;
use Adldap\Laravel\Listeners\LogImport;
use Adldap\Laravel\Tests\Models\TestUser;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Support\Facades\Log;
use Mockery as m;

class LogImportTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogImport();

        $user = m::mock(User::class);
        $model = m::mock(TestUser::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new Importing($user, $model);

        $logged = "User '{$name}' is being imported.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
