<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;

class Authenticating
{
    /**
     * The LDAP user that is authenticating.
     *
     * @var User
     */
    public $user;

    /**
     * The username that is being authenticated.
     *
     * @var string
     */
    public $username = '';

    /**
     * Constructor.
     *
     * @param User   $user
     * @param string $username
     */
    public function __construct(User $user, $username = '')
    {
        $this->user = $user;
        $this->username = $username;
    }
}
