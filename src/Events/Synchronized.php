<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class Synchronized
{
    /**
     * The LDAP user that was synchronized.
     *
     * @var User
     */
    public $user;

    /**
     * The LDAP users database model that was synchronized.
     *
     * @var Model
     */
    public $model;

    /**
     * Constructor.
     *
     * @param User  $user
     * @param Model $model
     */
    public function __construct(User $user, Model $model)
    {
        $this->user = $user;
        $this->model = $model;
    }
}
