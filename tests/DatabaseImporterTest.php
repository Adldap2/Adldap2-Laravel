<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Tests\Models\TestUser;

class DatabaseImporterTest extends DatabaseTestCase
{
    /** @test */
    public function ldap_users_are_imported()
    {
        $user = $this->makeLdapUser([
            'cn' => 'John Doe',
            'userprincipalname' => 'jdoe@email.com',
        ]);

        $importer = new Import($user, new TestUser());

        $model = $importer->handle();

        $this->assertEquals($user->getCommonName(), $model->name);
        $this->assertEquals($user->getUserPrincipalName(), $model->email);
        $this->assertFalse($model->exists);
    }
}
