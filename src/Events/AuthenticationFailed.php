<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;

class AuthenticationFailed
{
    /**
     * The user that failed authentication.
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
