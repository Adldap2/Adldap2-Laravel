<?php

namespace Adldap\Laravel\Traits;

use Adldap\Models\User;
use Adldap\Laravel\Events\AuthenticatedModelTrashed;
use Adldap\Laravel\Events\AuthenticatedWithCredentials;
use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Auth\Authenticatable;

trait AuthenticatesUsers
{
    use UsesAdldap;

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
     * Authenticates a user against our default LDAP connection.
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
     * Retrieves an LDAP user by their credentials.
     *
     * @param array $credentials
     *
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials = [])
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

                return $user;
            }
        }
    }

    /**
     * Handle an authenticated LDAP user with their model.
     *
     * @param User            $user
     * @param Authenticatable $model
     *
     * @return void
     */
    protected function handleAuthenticatedWithCredentials(User $user, Authenticatable $model)
    {
        Event::fire(new AuthenticatedWithCredentials($user, $model));
    }

    /**
     * Handle an authenticated users model that has been soft deleted.
     *
     * @param User            $user
     * @param Authenticatable $model
     *
     * @return void
     */
    protected function handleAuthenticatedModelTrashed(User $user, Authenticatable $model)
    {
        Event::fire(new AuthenticatedModelTrashed($user, $model));
    }

    /**
     * Handle discovered LDAP users before they are authenticated.
     *
     * @param User $user
     *
     * @return void
     */
    protected function handleDiscoveredUserWithCredentials(User $user)
    {
        Event::fire(new DiscoveredWithCredentials($user));
    }

    /**
     * Retrieves the configured login username from the LDAP user.
     *
     * @param User $user
     *
     * @return string
     */
    public function getLoginUsernameFromUser(User $user)
    {
        $username = $user->{$this->getLoginAttribute()};

        if (is_array($username)) {
            // We'll make sure we retrieve the users first username
            // attribute if it's contained in an array.
            $username = array_get($username, 0);
        }

        return $username;
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
     * Returns the configured username key.
     *
     * For example: 'email' or 'username'.
     *
     * @return string
     */
    protected function getUsernameKey()
    {
        return key($this->getUsernameAttribute());
    }

    /**
     * Returns the configured username value.
     *
     * For example: 'samaccountname' or 'mail'.
     *
     * @return string
     */
    protected function getUsernameValue()
    {
        return array_get($this->getUsernameAttribute(), $this->getUsernameKey());
    }

    /**
     * Returns the configured username attribute for discovering LDAP users.
     *
     * @return array
     */
    protected function getUsernameAttribute()
    {
        return config('adldap_auth.username_attribute', ['username' => $this->getSchema()->accountName()]);
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
     * Returns the configured login attribute for authenticating users.
     *
     * @return string
     */
    protected function getLoginAttribute()
    {
        return config('adldap_auth.login_attribute', $this->getSchema()->accountName());
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
