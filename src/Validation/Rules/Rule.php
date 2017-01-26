<?php

namespace Adldap\Laravel\Validation\Rules;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class Rule
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Model
     */
    protected $model;

    /**
     * Constructor.
     *
     * @param User $user
     * @param Model $model
     */
    public function __construct(User $user, Model $model)
    {
        $this->user = $user;
        $this->model = $model;
    }

    /**
     * Checks if the rule passes validation.
     *
     * @return bool
     */
    abstract public function isValid();
}
