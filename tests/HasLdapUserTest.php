<?php

namespace Adldap\Laravel\Tests;

use Mockery as m;
use Adldap\Models\User as LdapUser;
use Adldap\Laravel\Traits\HasLdapUser;
use Adldap\Laravel\Tests\Models\TestUser as EloquentUser;

class HasLdapUserTest extends TestCase
{
    /** @test */
    public function ldap_users_can_be_applied_to_model_when_trait_is_used()
    {
        $user = $this->createEloquentUser();

        $ldapUser = m::mock(LdapUser::class);

        $user->setLdapUser($ldapUser);

        $this->assertEquals($ldapUser, $user->ldap);
    }

    /** @test */
    public function null_ldap_user_can_be_given()
    {
        $user = $this->createEloquentUser();

        $user->setLdapUser(null);

        $this->assertNull($user->ldap);
    }

    /**
     * @return HasLdapUser
     */
    protected function createEloquentUser()
    {
        $user = new EloquentUser();

        if (! array_key_exists(HasLdapUser::class, class_uses(EloquentUser::class))) {
            $this->fail('TestUser model does not use '.HasLdapUser::class);
        }

        return $user;
    }
}
