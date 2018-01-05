<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthenticatedModelTrashed
{
    /**
     * The authenticated LDAP user.
     *
     * @var User
     */
    public $user;

    /**
     * The trashed authenticated LDAP users model.
     *
     * @var Authenticatable
     */
    public $model;

    /**
     * Constructor.
     *
     * @param User            $user
     * @param Authenticatable $model
     */
    public function __construct(User $user, Authenticatable $model)
    {
        $this->user = $user;
        $this->model = $model;
    }
}
