<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\Connections\ProviderInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class Resolver implements ResolverInterface
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
    public function authenticate(User $user, array $credentials = [])
    {
        $username = $user->getFirstAttribute($this->getLdapUsername());

        return $this->provider->auth()->attempt($username, $credentials['password']);
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $query = $this->provider->search()->users();

        foreach ($this->getScopes() as $scope) {
            // Create the scope.
            $scope = app($scope);

            // Apply it to our query.
            $scope->apply($query);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapUsername()
    {
        return config('adldap_auth.usernames.ldap', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getEloquentUsername()
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
