<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class Importing
{
    /**
     * The user being imported.
     *
     * @var User
     */
    public $user;

    /**
     * The model belonging to the user being imported.
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
