<?php

namespace Adldap\Laravel\Tests\Listeners;

use Adldap\Laravel\Events\AuthenticatedModelTrashed;
use Adldap\Laravel\Listeners\LogTrashedModel;
use Adldap\Laravel\Tests\TestCase;
use Adldap\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Mockery as m;

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
