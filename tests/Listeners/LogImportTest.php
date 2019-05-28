<?php

namespace Adldap\Laravel\Tests\Listeners;

use Mockery as m;
use Adldap\Models\User;
use Adldap\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Adldap\Laravel\Events\Importing;
use Adldap\Laravel\Listeners\LogImport;
use Adldap\Laravel\Tests\Models\TestUser;

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
