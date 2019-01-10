<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Tests\Models\TestUser;

class DatabaseImporterTest extends DatabaseTestCase
{
    /** @test */
    public function ldap_users_are_imported()
    {
        $user = $this->makeLdapUser();

        $importer = new Import($user, new TestUser());

        $model = $importer->handle();

        $this->assertEquals($user->getCommonName(), $model->name);
        $this->assertEquals($user->getUserPrincipalName(), $model->email);
        $this->assertFalse($model->exists);
    }

    /** @test */
    public function ldap_users_are_not_duplicated_with_alternate_casing()
    {
        $user = $this->makeLdapUser();

        $user->setUserPrincipalName('jdoe@EMAIL.com');

        $m1 = (new Import($user, new TestUser()))->handle();

        $m1->password = bcrypt(str_random(16));

        $m1->save();

        $m2 = (new Import($user, new TestUser()))->handle();

        $this->assertEquals($m1->id, $m2->id);
    }
}
