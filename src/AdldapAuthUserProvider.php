<?php

namespace Adldap\Laravel;

use Adldap\Laravel\Traits\ImportsUsers;
use Adldap\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class AdldapAuthUserProvider extends EloquentUserProvider
{
    use ImportsUsers;

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

        // If the user is an Adldap User model instance.
        if ($user instanceof User) {
            // Retrieve the password from the submitted credentials.
            $password = $this->getPasswordFromCredentials($credentials);

            // Construct / retrieve the eloquent model from our Adldap user.
            return $this->getModelFromAdldap($user, $password);
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
        if ($this->authenticateWithCredentials($credentials)) {
            // We've authenticated successfully, we'll finally
            // save the user to our local database.
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
     * Retrieves the Adldap User model from the specified Laravel model.
     *
     * @param mixed $model
     *
     * @return null|Authenticatable
     */
    protected function discoverAdldapFromModel($model)
    {
        if ($model instanceof Authenticatable && $this->getBindUserToModel()) {
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
        // Make sure we're connected to our LDAP server before we run any operations.
        if ($this->isConnected()) {
            // Retrieve the Adldap user.
            $user = $this->newAdldapUserQuery()->where([
                $this->getUsernameValue() => $this->getUsernameFromCredentials($credentials)
            ])->first();

            if ($user instanceof User) {
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
        return Arr::get($credentials, $this->getUsernameKey());
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
        return Arr::get($credentials, $this->getPasswordKey());
    }

    /**
     * Returns the password key to retrieve the
     * password from the user input array.
     *
     * @return mixed
     */
    protected function getPasswordKey()
    {
        return Config::get('adldap_auth.password_key', 'password');
    }

    /**
     * Retrieves the Adldap login fallback option for falling back
     * to the local database if AD authentication fails.
     *
     * @return bool
     */
    protected function getLoginFallback()
    {
        return Config::get('adldap_auth.login_fallback', false);
    }
}
