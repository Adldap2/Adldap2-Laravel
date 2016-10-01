<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Events\AuthenticatedModelTrashed;
use Adldap\Models\User;
use Adldap\Laravel\Traits\ImportsUsers;
use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Adldap\Laravel\Events\AuthenticatedWithCredentials;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class AdldapAuthUserProvider extends EloquentUserProvider
{
    use ImportsUsers;

    /**
     * The authenticated LDAP user.
     *
     * @var User|null
     */
    protected $user = null;

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
        $user = $this->authenticateWithCredentials($credentials);

        // Check if we've authenticated and retrieved an AD user.
        if ($user instanceof User && $this->user = $user) {
            // Retrieve the password from the submitted credentials.
            $password = $this->getPasswordFromCredentials($credentials);

            // Construct / retrieve the eloquent model from our Adldap user.
            $model = $this->getModelFromAdldap($user, $password);

            if (method_exists($model, 'trashed') && $model->trashed()) {
                // If the model is soft-deleted, we'll fire an event
                // with the affected LDAP user and their model.
                $this->handleAuthenticatedModelTrashed($user, $model);

                // We also won't allow soft-deleted users to authenticate.
                return;
            }

            // Perform other authenticated tasks.
            $this->handleAuthenticatedWithCredentials($user, $model);

            return $model;
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
            $this->saveModel($user);

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
     * Handle an authenticated LDAP user with their model.
     *
     * @param \Adldap\Models\User $user
     * @param Authenticatable     $model
     *
     * @return void
     */
    protected function handleAuthenticatedWithCredentials(User $user, $model)
    {
        Event::fire(new AuthenticatedWithCredentials($user, $model));
    }

    /**
     * Handle an authenticated users model that has been soft deleted.
     *
     * @param \Adldap\Models\User $user
     * @param Authenticatable     $model
     */
    protected function handleAuthenticatedModelTrashed(User $user, $model)
    {
        Event::fire(new AuthenticatedModelTrashed($user, $model));
    }

    /**
     * Handle discovered LDAP users before they are authenticated.
     *
     * @param \Adldap\Models\User $user
     *
     * @return void
     */
    protected function handleDiscoveredUserWithCredentials(User $user)
    {
        Event::fire(new DiscoveredWithCredentials($user));
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

    /**
     * Checks if we're currently connected to our configured LDAP server.
     *
     * @return bool
     */
    protected function isConnected()
    {
        return $this->getAdldap()->getConnection()->isBound();
    }

    /**
     * Authenticates a user against our LDAP connection.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function authenticate($username, $password)
    {
        return $this->getAdldap()->auth()->attempt($username, $password);
    }

    /**
     * Authenticates against Active Directory using the specified credentials.
     *
     * @param array $credentials
     *
     * @return User|false
     */
    protected function authenticateWithCredentials(array $credentials = [])
    {
        $username = $this->getUsernameFromCredentials($credentials);

        // Make sure we're connected to our LDAP server before we run any operations.
        if ($username && $this->isConnected()) {
            // Due to having the ability of choosing which attribute we login users
            // with, we actually need to retrieve the user from our LDAP server
            // before hand so we can retrieve these attributes.
            $user = $this->newAdldapUserQuery()->where([
                $this->getUsernameValue() => $username,
            ])->first();

            if ($user instanceof User) {
                // Perform operations on the discovered user.
                $this->handleDiscoveredUserWithCredentials($user);

                // Retrieve the authentication username for the AD user.
                $username = $this->getUsernameFromAdUser($user);

                // Retrieve the users password.
                $password = $this->getPasswordFromCredentials($credentials);

                // Perform LDAP authentication.
                if ($this->authenticate($username, $password)) {
                    // Passed, return the user instance.
                    return $user;
                }
            }
        }

        return false;
    }

    /**
     * Returns the username from the specified credentials.
     *
     * @param array $credentials
     *
     * @return string
     */
    protected function getUsernameFromCredentials(array $credentials = [])
    {
        return array_get($credentials, $this->getUsernameKey());
    }

    /**
     * Returns the configured users password from the credentials array.
     *
     * @param array $credentials
     *
     * @return string
     */
    protected function getPasswordFromCredentials(array $credentials = [])
    {
        return array_get($credentials, $this->getPasswordKey());
    }

    /**
     * Returns the password key to retrieve the
     * password from the user input array.
     *
     * @return mixed
     */
    protected function getPasswordKey()
    {
        return config('adldap_auth.password_key', 'password');
    }

    /**
     * Retrieves the Adldap login fallback option for falling back
     * to the local database if AD authentication fails.
     *
     * @return bool
     */
    protected function getLoginFallback()
    {
        return config('adldap_auth.login_fallback', false);
    }
}
