<?php

namespace Adldap\Laravel\Auth;

use Adldap\Auth\BindException;
use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Commands\SyncPassword;
use Adldap\Laravel\Events\AuthenticatedWithCredentials;
use Adldap\Laravel\Events\AuthenticationRejected;
use Adldap\Laravel\Events\AuthenticationSuccessful;
use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Adldap\Laravel\Events\Imported;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;

/** @mixin EloquentUserProvider */
class DatabaseUserProvider extends UserProvider
{
    use ForwardsCalls;

    /**
     * The currently authenticated LDAP user.
     *
     * @var User|null
     */
    protected $user;

    /**
     * The fallback eloquent user provider.
     *
     * @var EloquentUserProvider
     */
    protected $eloquent;

    /**
     * Constructor.
     *
     * @param HasherContract $hasher
     * @param string         $model
     */
    public function __construct(HasherContract $hasher, $model)
    {
        $this->eloquent = new EloquentUserProvider($hasher, $model);
    }

    /**
     * Forward missing method calls to the underlying Eloquent provider.
     *
     * @param string $method
     * @param mixed  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->eloquent, $method, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveById($identifier)
    {
        return $this->eloquent->retrieveById($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        return $this->eloquent->retrieveByToken($identifier, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $this->eloquent->updateRememberToken($user, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        $user = null;

        try {
            $user = Resolver::byCredentials($credentials);
        } catch (BindException $e) {
            if (! $this->isFallingBack()) {
                throw $e;
            }
        }

        if ($user instanceof User) {
            return $this->setAndImportAuthenticatingUser($user);
        }

        if ($this->isFallingBack()) {
            return $this->eloquent->retrieveByCredentials($credentials);
        }
    }

    /**
     * Set and import the authenticating LDAP user.
     *
     * @param User $user
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function setAndImportAuthenticatingUser(User $user)
    {
        // Set the currently authenticating LDAP user.
        $this->user = $user;

        Event::dispatch(new DiscoveredWithCredentials($user));

        // Import / locate the local user account.
        return Bus::dispatch(
            new Import($user, $this->eloquent->createModel())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $model, array $credentials)
    {
        // If the user exists in the local database, fallback is enabled,
        // and no LDAP user is was located for authentication, we will
        // perform standard eloquent authentication to "fallback" to.
        if (
            $model->exists
            && $this->isFallingBack()
            && ! $this->user instanceof User
        ) {
            return $this->eloquent->validateCredentials($model, $credentials);
        }

        if (! Resolver::authenticate($this->user, $credentials)) {
            return false;
        }

        Event::dispatch(new AuthenticatedWithCredentials($this->user, $model));

        // Here we will perform authorization on the LDAP user. If all
        // validation rules pass, we will allow the authentication
        // attempt. Otherwise, it is automatically rejected.
        if (! $this->passesValidation($this->user, $model)) {
            Event::dispatch(new AuthenticationRejected($this->user, $model));

            return false;
        }

        Bus::dispatch(new SyncPassword($model, $credentials));

        $model->save();

        if ($model->wasRecentlyCreated) {
            // If the model was recently created, they
            // have been imported successfully.
            Event::dispatch(new Imported($this->user, $model));
        }

        Event::dispatch(new AuthenticationSuccessful($this->user, $model));

        return true;
    }

    /**
     * Determines if login fallback is enabled.
     *
     * @return bool
     */
    protected function isFallingBack()
    {
        return Config::get('ldap_auth.login_fallback', false);
    }
}
