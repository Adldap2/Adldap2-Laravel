<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;

class AuthenticationRejected
{
    /**
     * The user that has been denied authentication.
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
