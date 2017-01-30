<?php

namespace Adldap\Laravel\Tests;

use Adldap\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

class NoDatabaseProviderTest extends NoDatabaseTestCase
{
    public function test_auth_passes($credentials = null)
    {
        $credentials = $credentials ?: ['email' => 'jdoe@email.com', 'password' => '12345'];

        $user = $this->getMockUser([
            'cn'             => '',
            'mail'           => 'jdoe@email.com',
            'samaccountname' => 'jdoe',
            'objectsid'      => 'S-1-5-32-544',
        ]);

        $connection = $this->getMockConnection();

        $connection->expects($this->exactly(1))->method('isBound')->willReturn(true);

        $connection->expects($this->exactly(1))->method('search')->willReturn('resource');

        $connection->expects($this->exactly(1))->method('getEntries')->willReturn([
            'count' => 1,
            $user->getAttributes(),
        ]);

        $connection->expects($this->exactly(2))->method('bind')
            ->with($this->logicalOr(
                $this->equalTo('jdoe'),
                $this->equalTo('admin')
            ))
            ->willReturn(true);

        Event::shouldReceive('fire')->between(0, 5)->withAnyArgs();
        Event::shouldReceive('dispatch')->between(0, 5)->withAnyArgs();

        $this->assertTrue(Auth::attempt($credentials));

        $user = Auth::user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($credentials['email'], $user->mail[0]);
    }
}
