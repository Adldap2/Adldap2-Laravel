<?php

namespace Adldap\Laravel\Traits;

use Adldap\Models\User;

trait HasLdapUser
{
    /**
     * The LDAP User that is bound to the current model.
     *
     * @var User|null
     */
    public $ldap;

    /**
     * Sets the LDAP user that is bound to the model.
     *
     * @param User $user
     */
    public function setLdapUser(User $user = null)
    {
        $this->ldap = $user;
    }
}
