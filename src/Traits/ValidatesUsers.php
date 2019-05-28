<?php

namespace Adldap\Laravel\Traits;

use Adldap\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Adldap\Laravel\Validation\Validator;

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
        return (new Validator(
            $this->rules($user, $model)
        ))->passes();
    }

    /**
     * Returns an array of constructed rules.
     *
     * @param User       $user
     * @param Model|null $model
     *
     * @return array
     */
    protected function rules(User $user, Model $model = null)
    {
        $rules = [];

        foreach ($this->getRules() as $rule) {
            $rules[] = new $rule($user, $model);
        }

        return $rules;
    }

    /**
     * Retrieves the configured authentication rules.
     *
     * @return array
     */
    protected function getRules()
    {
        return Config::get('ldap_auth.rules', []);
    }
}
