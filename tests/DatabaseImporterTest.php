<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Tests\Models\TestUser;
use Illuminate\Support\Str;

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
        $firstUser = $this->makeLdapUser();

        $firstUser->setUserPrincipalName('JDOE@EMAIL.com');

        $m1 = (new Import($firstUser, new TestUser()))->handle();

        $m1->password = bcrypt(Str::random(16));

        $m1->save();

        $secondUser = $this->makeLdapUser();

        $secondUser->setUserPrincipalName('jdoe@email.com');

        $m2 = (new Import($secondUser, new TestUser()))->handle();

        $this->assertTrue($m1->is($m2));
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function exception_is_thrown_when_guid_is_null()
    {
        $u = $this->makeLdapUser([
            'objectguid' => null,
        ]);

        (new Import($u, new TestUser()))->handle();
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function exception_is_thrown_when_guid_is_empty()
    {
        $u = $this->makeLdapUser([
            'objectguid' => ' ',
        ]);

        (new Import($u, new TestUser()))->handle();
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function exception_is_thrown_when_username_is_null()
    {
        $u = $this->makeLdapUser([
            'userprincipalname' => null,
        ]);

        (new Import($u, new TestUser()))->handle();
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function exception_is_thrown_when_username_is_empty()
    {
        $u = $this->makeLdapUser([
            'userprincipalname' => ' ',
        ]);

        (new Import($u, new TestUser()))->handle();
    }
}
