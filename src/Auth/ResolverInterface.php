<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\Connections\ProviderInterface;
use Illuminate\Contracts\Auth\Authenticatable;

interface ResolverInterface
{
    /**
     * Constructor.
     *
     * @param ProviderInterface $provider
     */
    public function __construct(ProviderInterface $provider);

    /**
     * Retrieves a user by the given identifier.
     *
     * @param string $identifier
     *
     * @return \Adldap\Models\Model|null
     */
    public function byId($identifier);

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     *
     * @return \Adldap\Models\User|null
     */
    public function byCredentials(array $credentials = []);

    /**
     * Retrieve a user by the given model.
     *
     * @param Authenticatable $model
     *
     * @return \Adldap\Models\User|null
     */
    public function byModel(Authenticatable $model);

    /**
     * Authenticates the user against the current provider.
     *
     * @param User  $user
     * @param array $credentials
     *
     * @return string|null
     */
    public function authenticate(User $user, array $credentials = []);

    /**
     * Returns a new user query.
     *
     * @return \Adldap\Query\Builder
     */
    public function query();

    /**
     * Retrieves the configured LDAP users username attribute.
     *
     * @return string
     */
    public function getLdapUsername();

    /**
     * Retrieves the configured eloquent users username attribute.
     *
     * @return string
     */
    public function getEloquentUsername();
}
