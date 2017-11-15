<?php

namespace Adldap\Laravel\Resolvers;

use Adldap\Models\User;
use Adldap\AdldapInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Auth\Authenticatable;

class UserResolver implements ResolverInterface
{
    /**
     * The underlying Adldap instance.
     *
     * @var AdldapInterface
     */
    protected $ldap;

    /**
     * The LDAP connection to utilize.
     *
     * @var string
     */
    protected $connection = 'default';

    /**
     * {@inheritdoc}
     */
    public function __construct(AdldapInterface $ldap)
    {
        $this->ldap = $ldap;
    }

    /**
     * Sets the LDAP connection to use.
     *
     * @param string $connection
     *
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function byId($identifier)
    {
        return $this->query()->findByGuid($identifier);
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
            ->whereEquals($this->getLdapDiscoveryAttribute(), $credentials[$this->getEloquentUsernameAttribute()])
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function byModel(Authenticatable $model)
    {
        return $this->query()
            ->whereEquals($this->getLdapDiscoveryAttribute(), $model->{$this->getEloquentUsernameAttribute()})
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(User $user, array $credentials = [])
    {
        $username = $user->getFirstAttribute($this->getLdapAuthAttribute());

        return $this->getProvider()->auth()->attempt($username, $credentials['password']);
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $query = $this->getProvider()->search()->users();

        foreach ($this->getScopes() as $scope) {
            // Create the scope.
            $scope = app($scope);

            // Apply it to our query.
            $scope->apply($query);
        }

        return $query;
    }

    /**
     * Returns the configured connection provider.
     *
     * @return \Adldap\Connections\ProviderInterface
     */
    protected function getProvider()
    {
        return $this->ldap->getProvider($this->connection);
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapDiscoveryAttribute()
    {
        return Config::get('adldap_auth.usernames.ldap.discover', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapAuthAttribute()
    {
        return Config::get('adldap_auth.usernames.ldap.authenticate', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getEloquentUsernameAttribute()
    {
        return Config::get('adldap_auth.usernames.eloquent', 'email');
    }

    /**
     * Returns the configured query scopes.
     *
     * @return array
     */
    protected function getScopes()
    {
        return Config::get('adldap_auth.scopes', []);
    }
}
