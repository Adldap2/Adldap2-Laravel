<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuthenticationRejected
{
    /**
     * The user that has been denied authentication.
     *
     * @var User
     */
    public $user;

    /**
     * The LDAP users eloquent model.
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
