<?php

namespace Adldap\Laravel\Resolvers;

use Adldap\AdldapInterface;
use Adldap\Connections\ProviderInterface;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Laravel\Events\Authenticated;
use Adldap\Laravel\Events\Authenticating;
use Adldap\Laravel\Events\AuthenticationFailed;
use Adldap\Models\User;
use Adldap\Query\Builder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use RuntimeException;

class UserResolver implements ResolverInterface
{
    /**
     * The Adldap instance.
     *
     * @var AdldapInterface
     */
    protected $ldap;

    /**
     * The name of the LDAP connection to utilize.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function __construct(AdldapInterface $ldap)
    {
        $this->ldap = $ldap;
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
        if ($user = $this->query()->findByGuid($identifier)) {
            return $user;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function byCredentials(array $credentials = [])
    {
        if (empty($credentials)) {
            return;
        }

        // Depending on the configured user provider, the
        // username field will differ for retrieving
        // users by their credentials.
        $attribute = $this->getAppAuthProvider() instanceof NoDatabaseUserProvider ?
            $this->getLdapDiscoveryAttribute() :
            $this->getDatabaseUsernameColumn();

        if (! array_key_exists($attribute, $credentials)) {
            throw new RuntimeException(
                "The '$attribute' key is missing from the given credentials array."
            );
        }

        return $this->query()->whereEquals(
            $this->getLdapDiscoveryAttribute(),
            $credentials[$attribute]
        )->first();
    }

    /**
     * {@inheritdoc}
     */
    public function byModel(Authenticatable $model)
    {
        return $this->byId($model->{$this->getDatabaseIdColumn()});
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

        Event::dispatch(new Authenticating($user, $username));

        if ($this->getLdapAuthProvider()->auth()->attempt($username, $password)) {
            Event::dispatch(new Authenticated($user));

            return true;
        }

        Event::dispatch(new AuthenticationFailed($user));

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function query(): Builder
    {
        $query = $this->getLdapAuthProvider()->search()->users();

        // We will ensure our object GUID attribute is always selected
        // along will all attributes. Otherwise, if the object GUID
        // attribute is virtual, it may not be returned.
        $selects = array_unique(array_merge(['*', $query->getSchema()->objectGuid()], $query->getSelects()));

        $query->select($selects);

        foreach ($this->getQueryScopes() as $scope) {
            app($scope)->apply($query);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapDiscoveryAttribute(): string
    {
        return Config::get('ldap_auth.identifiers.ldap.locate_users_by', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapAuthAttribute(): string
    {
        return Config::get('ldap_auth.identifiers.ldap.bind_users_by', 'distinguishedname');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseUsernameColumn(): string
    {
        return Config::get('ldap_auth.identifiers.database.username_column', 'email');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseIdColumn(): string
    {
        return Config::get('ldap_auth.identifiers.database.guid_column', 'objectguid');
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
        return Arr::get($credentials, 'password');
    }

    /**
     * Retrieves the provider for the current connection.
     *
     * @throws \Adldap\AdldapException
     *
     * @return ProviderInterface
     */
    protected function getLdapAuthProvider(): ProviderInterface
    {
        $provider = $this->ldap->getProvider($this->connection ?? $this->getLdapAuthConnectionName());

        if (! $provider->getConnection()->isBound()) {
            // We'll make sure we have a bound connection before
            // allowing dynamic calls on the default provider.
            $provider->connect();
        }

        return $provider;
    }

    /**
     * Returns the default guards provider instance.
     *
     * @return UserProvider
     */
    protected function getAppAuthProvider(): UserProvider
    {
        return Auth::guard()->getProvider();
    }

    /**
     * Returns the connection name of the authentication provider.
     *
     * @return string
     */
    protected function getLdapAuthConnectionName()
    {
        return Config::get('ldap_auth.connection', 'default');
    }

    /**
     * Returns the configured query scopes.
     *
     * @return array
     */
    protected function getQueryScopes()
    {
        return Config::get('ldap_auth.scopes', []);
    }
}
