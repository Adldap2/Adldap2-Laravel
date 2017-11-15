<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;

class Authenticated
{
    /**
     * The LDAP user that has successfully authenticated.
     *
     * @var User
     */
    public $user;

    /**
     * Constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
