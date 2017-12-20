<?php

namespace Adldap\Laravel\Resolvers;

use Adldap\Models\User;
use Adldap\Query\Builder;
use Adldap\AdldapInterface;
use Illuminate\Contracts\Auth\Authenticatable;

interface ResolverInterface
{
    /**
     * Constructor.
     *
     * @param AdldapInterface $ldap
     */
    public function __construct(AdldapInterface $ldap);

    /**
     * Sets the LDAP connection to use.
     *
     * @param string $connection
     *
     * @return void
     */
    public function setConnection($connection);

    /**
     * Retrieves a user by the given identifier.
     *
     * @param string $identifier The users object GUID.
     *
     * @return \Adldap\Models\Model|null
     */
    public function byId($identifier);

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array  $credentials The users credentials
     *
     * @return \Adldap\Models\User|null
     */
    public function byCredentials(array $credentials = []);

    /**
     * Retrieve a user by the given model.
     *
     * @param Authenticatable $model The users eloquent model
     *
     * @return \Adldap\Models\User|null
     */
    public function byModel(Authenticatable $model);

    /**
     * Authenticates the user against the current provider.
     *
     * @param User  $user        The LDAP users model
     * @param array $credentials The LDAP users credentials
     *
     * @return string|null
     */
    public function authenticate(User $user, array $credentials = []);

    /**
     * Returns a new user query.
     *
     * @return \Adldap\Query\Builder
     */
    public function query() : Builder;

    /**
     * Retrieves the configured LDAP users username attribute.
     *
     * @return string
     */
    public function getLdapDiscoveryAttribute() : string;

    /**
     * Retrieves the configured LDAP users authenticatable username attribute.
     *
     * @return string
     */
    public function getLdapAuthAttribute() : string;

    /**
     * Retrieves the configured eloquent users username attribute.
     *
     * @return string
     */
    public function getEloquentUsernameAttribute() : string;
}
