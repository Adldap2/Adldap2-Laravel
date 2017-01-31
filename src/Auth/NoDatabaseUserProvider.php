<?php

namespace Adldap\Laravel\Auth;

use Adldap\Laravel\Traits\AuthenticatesUsers;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class NoDatabaseUserProvider implements UserProvider
{
    use AuthenticatesUsers {
        retrieveByCredentials as retrieveLdapUserByCredentials;
    }

    /**
     *  {@inheritdoc}
     */
    public function retrieveById($identifier)
    {
        $user = $this->newAdldapUserQuery()->where([
            $this->getSchema()->objectSid() => $identifier,
        ])->first();

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
        return $this->retrieveLdapUserByCredentials($credentials);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Retrieve the authentication username for the AD user.
        $username = $this->getLoginUsernameFromUser($user);

        // Perform LDAP authentication.
        return $this->authenticate($username, $credentials['password']);
    }
}
