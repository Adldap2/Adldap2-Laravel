<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\Laravel\Traits\ImportsUsers;
use Adldap\Laravel\Validation\Validator;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class DatabaseUserProvider extends EloquentUserProvider
{
    use ImportsUsers {
        retrieveByCredentials as retrieveLdapUserByCredentials;
    }

    /**
     * The currently authenticated LDAP user.
     *
     * @var User
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function retrieveById($identifier)
    {
        $model = parent::retrieveById($identifier);

        $this->locateAndBindLdapUserToModel($model);

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        $model = parent::retrieveByToken($identifier, $token);

        $this->locateAndBindLdapUserToModel($model);

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        $user = $this->retrieveLdapUserByCredentials($credentials);

        if ($user instanceof User) {
            // Set the authenticating LDAP user.
            $this->user = $user;

            return $this->findOrCreateModelByUser($user, $credentials['password']);
        }

        // If we're unable to locate the user, we'll either fallback to local
        if ($this->getLoginFallback()) {
            return parent::retrieveByCredentials($credentials);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $model, array $credentials)
    {
        if ($this->user instanceof User) {
            // We'll retrieve the login name from the LDAP user.
            $username = $this->getLoginUsernameFromUser($this->user);

            // Perform LDAP authentication.
            if ($this->authenticate($username, $credentials['password'])) {
                $this->handleAuthenticatedWithCredentials($this->user, $model);

                // Here we will perform authorization on the LDAP user. If all
                // validation rules pass, we will allow the authentication
                // attempt. Otherwise, it is automatically rejected.
                if ($this->validator($this->rules($this->user, $model))->passes()) {
                    // All of our validation rules have passed and we can
                    // finally save the model in case of changes.
                    $model->save();

                    return true;
                }

                return false;
            }
        }

        if ($this->getLoginFallback() && $model->exists) {
            // If the user exists in our local database already and fallback is
            // enabled, we'll perform standard eloquent authentication.
            return parent::validateCredentials($model, $credentials);
        }

        return false;
    }

    /**
     * Returns a new authentication validator.
     *
     * @param array $rules
     *
     * @return Validator
     */
    protected function validator(array $rules = [])
    {
        return new Validator($rules);
    }

    /**
     * Returns an array of constructed rules.
     *
     * @param User            $user
     * @param Authenticatable $model
     *
     * @return array
     */
    protected function rules(User $user, Authenticatable $model)
    {
        $rules = [];

        foreach (config('adldap_auth.rules', []) as $rule) {
            $rules[] = new $rule($user, $model);
        }

        return $rules;
    }
}
