<?php

namespace Adldap\Laravel\Validation\Rules;

use Adldap\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class Rule
{
    /**
     * The LDAP user.
     *
     * @var User
     */
    protected $user;

    /**
     * The Eloquent model.
     *
     * @var Model|null
     */
    protected $model;

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

    /**
     * Checks if the rule passes validation.
     *
     * @return bool
     */
    abstract public function isValid();
}
