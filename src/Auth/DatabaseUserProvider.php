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
            // We'll retrieve the login name from the LDAP user.
            $username = $this->getLoginUsernameFromUser($user);

            // Then, get their password from the given credentials.
            $password = $this->getPasswordFromCredentials($credentials);

            // Perform LDAP authentication.
            if ($this->authenticate($username, $password)) {
                // Passed, find or create the eloquent model from our LDAP user.
                $model = $this->findOrCreateModelFromAdldap($user, $password);

                $this->handleAuthenticatedWithCredentials($user, $model);

                if ($this->validator($this->rules($user, $model))->passes()) {
                    // Set the currently authenticated LDAP user.
                    $this->user = $user;

                    return $model;
                }
            }
        }

        // Login failed. If login fallback is enabled
        // we'll call the eloquent driver.
        if ($this->getLoginFallback()) {
            return parent::retrieveByCredentials($credentials);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Check if we have an authenticated AD user.
        if ($this->user instanceof User) {
            // We'll save the authenticated model in case of changes.
            $user->save();

            return true;
        }

        if ($this->getLoginFallback() && $user->exists) {
            // If the user exists in our local database already and fallback is
            // enabled, we'll perform standard eloquent authentication.
            return parent::validateCredentials($user, $credentials);
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
