<?php

namespace Adldap\Laravel\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class NoDatabaseUserProvider extends Provider
{
    /**
     *  {@inheritdoc}
     */
    public function retrieveById($identifier)
    {
        $user = $this->resolver()->byId($identifier);

        if ($user instanceof Authenticatable) {
            // We'll verify we have the correct instance just to ensure we
            // don't return an incompatible model that may be returned.
            return $user;
        }
    }

    /**
     *  {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        return;
    }

    /**
     *  {@inheritdoc}
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        //
    }

    /**
     *  {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        return $this->resolver()->byCredentials($credentials);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Retrieve the authentication username for the AD user.
        $username = $this->resolver()->username($user);

        // Perform LDAP authentication.
        return $this->provider()->auth()->attempt($username, $credentials['password']);
    }
}
