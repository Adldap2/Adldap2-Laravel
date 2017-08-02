<?php

namespace Adldap\Laravel\Traits;

use Adldap\Models\User;
use Adldap\Laravel\Validation\Validator;
use Illuminate\Database\Eloquent\Model;

trait ValidatesUsers
{
    /**
     * Determines if the model passes validation.
     *
     * @param User  $user
     * @param Model $model
     *
     * @return bool
     */
    protected function passesValidation(User $user, Model $model = null)
    {
        return $this->newValidator($this->getRules($user, $model))->passes();
    }

    /**
     * Returns an array of constructed rules.
     *
     * @param User       $user
     * @param Model|null $model
     *
     * @return array
     */
    protected function getRules(User $user, Model $model = null)
    {
        $rules = [];

        foreach (config('adldap_auth.rules', []) as $rule) {
            $rules[] = new $rule($user, $model);
        }

        return $rules;
    }

    /**
     * Returns a new authentication validator.
     *
     * @param array $rules
     *
     * @return Validator
     */
    protected function newValidator(array $rules = [])
    {
        return new Validator($rules);
    }
}
