<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class Imported
{
    /**
     * The LDAP user that was successfully imported.
     *
     * @var User
     */
    public $user;

    /**
     * The model belonging to the user that was imported.
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
