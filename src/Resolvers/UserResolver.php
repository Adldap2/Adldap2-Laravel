<?php

namespace Adldap\Laravel\Resolvers;

use Adldap\Models\User;
use Adldap\Query\Builder;
use Adldap\AdldapInterface;
use Adldap\Connections\ProviderInterface;
use Adldap\Laravel\Events\Authenticated;
use Adldap\Laravel\Events\Authenticating;
use Adldap\Laravel\Events\AuthenticationFailed;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Auth\Authenticatable;

class UserResolver implements ResolverInterface
{
    /**
     * The Adldap instance.
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

        $this->setConnection($this->getAuthConnection());
    }

    /**
     * {@inheritdoc}
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

        $provider = Config::get('adldap_auth.provider', DatabaseUserProvider::class);

        // Depending on the configured user provider, the
        // username field will differ for retrieving
        // users by their credentials.
        if ($provider == NoDatabaseUserProvider::class) {
            $username = $credentials[$this->getLdapDiscoveryAttribute()];
        } else {
            $username = $credentials[$this->getEloquentUsernameAttribute()];
        }

        $field = $this->getLdapDiscoveryAttribute();

        return $this->query()->whereEquals($field, $username)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function byModel(Authenticatable $model)
    {
        $field = $this->getLdapDiscoveryAttribute();

        $username = $model->{$this->getEloquentUsernameAttribute()};

        return $this->query()->whereEquals($field, $username)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(User $user, array $credentials = [])
    {
        $attribute = $this->getLdapAuthAttribute();

        // If the developer has inserted 'dn' as their LDAP
        // authentication attribute, we'll convert it to
        // the full attribute name for convenience.
        if ($attribute == 'dn') {
            $attribute = $user->getSchema()->distinguishedName();
        }

        $username = $user->getFirstAttribute($attribute);

        $password = $this->getPasswordFromCredentials($credentials);

        Event::fire(new Authenticating($user, $username));

        if ($this->getProvider()->auth()->attempt($username, $password)) {
            Event::fire(new Authenticated($user));

            return true;
        }

        Event::fire(new AuthenticationFailed($user));

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function query() : Builder
    {
        $query = $this->getProvider()->search()->users();

        $scopes = Config::get('adldap_auth.scopes', []);

        if (is_array($scopes)) {
            foreach ($scopes as $scope) {
                // Here we will use Laravel's IoC container to construct our scope.
                // This allows us to utilize any Laravel dependencies in
                // the scopes constructor that may be needed.

                /** @var \Adldap\Laravel\Scopes\ScopeInterface $scope */
                $scope = app($scope);

                // With the scope constructed, we can apply it to our query.
                $scope->apply($query);
            }
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapDiscoveryAttribute() : string
    {
        return Config::get('adldap_auth.usernames.ldap.discover', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapAuthAttribute() : string
    {
        return Config::get('adldap_auth.usernames.ldap.authenticate', 'distinguishedname');
    }

    /**
     * {@inheritdoc}
     */
    public function getEloquentUsernameAttribute() : string
    {
        return Config::get('adldap_auth.usernames.eloquent', 'email');
    }

    /**
     * Returns the password field to retrieve from the credentials.
     *
     * @param array $credentials
     *
     * @return string|null
     */
    protected function getPasswordFromCredentials($credentials)
    {
        return array_get($credentials, 'password');
    }

    /**
     * Retrieves the provider for the current connection.
     *
     * @throws \Adldap\AdldapException
     *
     * @return \Adldap\Connections\ProviderInterface
     */
    protected function getProvider() : ProviderInterface
    {
        return $this->ldap->getProvider($this->connection);
    }

    /**
     * Returns the connection name of the authentication provider.
     *
     * @return string
     */
    protected function getAuthConnection()
    {
        return Config::get('adldap_auth.connection', 'default');
    }
}
