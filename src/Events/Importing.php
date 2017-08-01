<?php

namespace Adldap\Laravel\Events;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

class Importing
{
    /**
     * The user being synchronized.
     *
     * @var User
     */
    public $user;

    /**
     * The model belonging to the user being synchronized.
     *
     * @var Model
     */
    public $model;

    /**
     * The users credentials.
     *
     * @var array
     */
    public $credentials;

    /**
     * Constructor.
     *
     * @param User  $user
     * @param Model $model
     * @param array $credentials
     */
    public function __construct(User $user, Model $model, array $credentials = [])
    {
        $this->user = $user;
        $this->model = $model;
        $this->credentials = $credentials;
    }
}
