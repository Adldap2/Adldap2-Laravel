<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\Laravel\Traits\ImportsUsers;
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

        return $this->discoverAdldapFromModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        $model = parent::retrieveByToken($identifier, $token);

        return $this->discoverAdldapFromModel($model);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        $user = $this->retrieveLdapUserByCredentials($credentials);

        // We need to check if we have the right user instance, as well
        // as set the currently authenticated user in this case.
        if ($user instanceof User && $this->user = $user) {
            // We'll retrieve the login name from the LDAP user.
            $username = $this->getLoginUsernameFromUser($user);

            // Then, get their password from the given credentials.
            $password = $this->getPasswordFromCredentials($credentials);

            // Perform LDAP authentication.
            if ($this->authenticate($username, $password)) {
                // Passed, create / find the eloquent model from our Adldap user.
                $model = $this->getModelFromAdldap($user, $password);

                if (method_exists($model, 'trashed') && $model->trashed()) {
                    // If the model is soft-deleted, we'll fire an event
                    // with the affected LDAP user and their model.
                    $this->handleAuthenticatedModelTrashed($user, $model);

                    // We also won't allow soft-deleted users to authenticate.
                    return;
                }

                if ($this->getOnlyAllowImportedUsers() && ! $model->exists) {
                    // If we're only allowing already imported users
                    // and the user doesn't exist, we won't
                    // allow them to authenticate.
                    return;
                }

                return $model;
            }
        }

        if ($this->getLoginFallback()) {
            // Login failed. If login fallback is enabled
            // we'll call the eloquent driver.
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
     * Retrieves the Adldap User model from the specified Laravel model.
     *
     * @param mixed $model
     *
     * @return null|Authenticatable
     */
    protected function discoverAdldapFromModel($model)
    {
        if ($this->getBindUserToModel() && $model) {
            // If the developer wants to bind the Adldap User model
            // to the Laravel model, we'll query to find it.
            $attributes = $this->getUsernameAttribute();

            $key = key($attributes);

            $user = $this->newAdldapUserQuery()
                ->where([$attributes[$key] => $model->{$key}])
                ->first();

            if ($user instanceof User) {
                $model = $this->bindAdldapToModel($user, $model);
            }
        }

        return $model;
    }
}
