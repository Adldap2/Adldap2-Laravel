<?php

namespace Adldap\Laravel\Tests\Listeners;

use Mockery as m;
use Adldap\Models\User;
use Adldap\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Adldap\Laravel\Listeners\LogTrashedModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Adldap\Laravel\Events\AuthenticatedModelTrashed;

class LogTrashedModelTest extends TestCase
{
    /** @test */
    public function logged()
    {
        $l = new LogTrashedModel();

        $user = m::mock(User::class);

        $name = 'John Doe';

        $user->shouldReceive('getCommonName')->andReturn($name);

        $e = new AuthenticatedModelTrashed($user, m::mock(Authenticatable::class));

        $logged = "User '{$name}' was denied authentication because their model has been soft-deleted.";

        Log::shouldReceive('info')->once()->with($logged);

        $l->handle($e);
    }
}
