<?php

namespace Adldap\Laravel\Resolvers;

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
     * Retrieves the authentication username for the given user.
     *
     * @param User $user
     *
     * @return string|null
     */
    public function username(User $user);

    /**
     * Returns a new user query.
     *
     * @return \Adldap\Query\Builder
     */
    public function query();
}
