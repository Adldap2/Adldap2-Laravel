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
        $user = $this->getResolver()->byId($identifier);

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
        if ($user = $this->getResolver()->byCredentials($credentials)) {
            $this->handleDiscoveredWithCredentials($user);

            return $user;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Perform LDAP authentication and validate the authenticated model.
        if (
            $this->getResolver()->authenticate($user, $credentials) &&
            $this->newValidator($this->getRules($user))->passes()
        ) {
            $this->handleAuthenticatedWithCredentials($user);

            return true;
        }

        return false;
    }
}
