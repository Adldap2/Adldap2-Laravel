<?php

namespace Adldap\Laravel\Resolvers;

use Adldap\Models\User;
use Adldap\Query\Builder;
use Adldap\Connections\ProviderInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class UserResolver implements ResolverInterface
{
    /**
     * The LDAP connection provider.
     *
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function byId($identifier)
    {
        return $this->query()->where([
            $this->provider->getSchema()->objectSid() => $identifier,
        ])->first();
    }

    /**
     * {@inheritdoc}
     */
    public function byCredentials(array $credentials = [])
    {
        if (empty($credentials)) {
            return;
        }

        return $this->query()
            ->whereEquals($this->getLdapUsername(), $credentials[$this->getEloquentUsername()])
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function byModel(Authenticatable $model)
    {
        return $this->query()
            ->whereEquals($this->getLdapUsername(), $model->{$this->getEloquentUsername()})
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function username(User $user)
    {
        return $user->getFirstAttribute($this->getLdapUsername());
    }

    /**
     * Returns a new Adldap user query.
     *
     * @return Builder
     */
    public function query()
    {
        $query = $this->provider->search()->users();

        foreach ($this->getScopes() as $scope) {
            $scope = app($scope);

            $scope->apply($query);
        }

        return $query;
    }

    /**
     * Retrieves the configured LDAP users username attribute.
     *
     * @return string
     */
    protected function getLdapUsername()
    {
        return config('adldap_auth.usernames.ldap', 'mail');
    }

    /**
     * Retrieves the configured eloquent users username attribute.
     *
     * @return string
     */
    protected function getEloquentUsername()
    {
        return config('adldap_auth.usernames.eloquent', 'email');
    }

    /**
     * Returns the configured query scopes.
     *
     * @return array
     */
    protected function getScopes()
    {
        return config('adldap_auth.scopes', []);
    }
}
