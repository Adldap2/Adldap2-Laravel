<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Auth\Importer;
use Adldap\Laravel\Tests\Models\User;

class DatabaseImporterTest extends DatabaseTestCase
{
    public function test_run()
    {
        $user = $this->makeLdapUser([
            'cn' => 'John Doe',
            'userprincipalname' => 'jdoe@email.com',
        ]);

        $importer = new Importer();

        $model = $importer->run($user, new User());

        $this->assertEquals($user->getCommonName(), $model->name);
        $this->assertEquals($user->getUserPrincipalName(), $model->email);
        $this->assertFalse($model->exists);
    }
}
