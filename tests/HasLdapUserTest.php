<?php
namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Tests\Models\User as EloquentUser;
use Adldap\Laravel\Traits\HasLdapUser;
use Adldap\Models\User as LdapUser;

class HasLdapUserTest extends \PHPUnit_Framework_TestCase
{
    public function testSetLdapUserSetsUserOnModel()
    {
        $user = $this->createEloquentUser();
        $ldapUser = \Mockery::mock(LdapUser::class);

        $user->setLdapUser($ldapUser);

        $this->assertEquals($ldapUser, $user->ldap);
    }


    public function testSetLdapUserWithNull()
    {
        $user = $this->createEloquentUser();

        $user->setLdapUser(null);

        $this->assertNull($user->ldap);
    }

    /**
     * @return HasLdapUser
     */
    private function createEloquentUser()
    {
        $user = new EloquentUser();

        if (! array_key_exists(HasLdapUser::class, class_uses(EloquentUser::class))) {
            $this->fail('User model does not use ' . HasLdapUser::class);
        }

        return $user;
    }
}
