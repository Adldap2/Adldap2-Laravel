<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuthenticationSuccessful
{
    /**
     * The LDAP user that has successfully authenticated.
     *
     * @var User
     */
    public $user;

    /**
     * The authenticated LDAP users model.
     *
     * @var Model|null
     */
    public $model;

    /**
     * Constructor.
     *
     * @param User       $user
     * @param Model|null $model
     */
    public function __construct(User $user, Model $model = null)
    {
        $this->user = $user;
        $this->model = $model;
    }
}
