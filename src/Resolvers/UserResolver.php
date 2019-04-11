<?php

namespace Adldap\Laravel\Resolvers;

use RuntimeException;
use Adldap\Models\User;
use Adldap\Query\Builder;
use Adldap\AdldapInterface;
use Adldap\Connections\ProviderInterface;
use Adldap\Laravel\Events\Authenticated;
use Adldap\Laravel\Events\Authenticating;
use Adldap\Laravel\Events\AuthenticationFailed;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Auth\UserProvider;
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
    public function query() : Builder
    {
        $query = $this->getLdapAuthProvider()->search()->users();

        // We will ensure our object GUID attribute is always selected
        // along will all attributes. Otherwise, if the object GUID
        // attribute is virtual, it may not be returned.
        $selects = array_unique(array_merge(['*', $query->getSchema()->objectGuid()], $query->getSelects()));

        $query->select($selects);

        $scopes = Config::get('ldap_auth.scopes', []);

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
        return Config::get('ldap_auth.identifiers.ldap.locate_users_by', 'userprincipalname');
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapAuthAttribute() : string
    {
        return Config::get('ldap_auth.identifiers.ldap.bind_users_by', 'distinguishedname');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseUsernameColumn() : string
    {
        return Config::get('ldap_auth.identifiers.database.username_column', 'email');
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseIdColumn() : string
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
        return array_get($credentials, 'password');
    }

    /**
     * Retrieves the provider for the current connection.
     *
     * @throws \Adldap\AdldapException
     *
     * @return ProviderInterface
     */
    protected function getLdapAuthProvider() : ProviderInterface
    {
        return $this->ldap->getProvider($this->connection ?? $this->getLdapAuthConnectionName());
    }

    /**
     * Returns the default guards provider instance.
     *
     * @return UserProvider
     */
    protected function getAppAuthProvider() : UserProvider
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
}
