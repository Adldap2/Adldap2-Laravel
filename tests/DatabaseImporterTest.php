<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Auth\Importer;
use Adldap\Laravel\Tests\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseImporterTest extends DatabaseTestCase
{
    public function test_run()
    {
        $user = $this->makeLdapUser([
            'cn' => 'John Doe',
            'userprincipalname' => 'jdoe@email.com',
        ]);

        $importer = new Importer();

        $model = $importer->run($user, new User(), ['password' => 'password']);

        $this->assertEquals($user->getCommonName(), $model->name);
        $this->assertEquals($user->getUserPrincipalName(), $model->email);
        $this->assertTrue(Hash::check('password', $model->password));
    }
}
