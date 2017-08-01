<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class DatabaseUserProvider extends Provider
{
    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * The fallback user provider.
     *
     * @var UserProvider
     */
    protected $fallback;

    /**
     * The currently authenticated LDAP user.
     *
     * @var User|null
     */
    protected $user;

    /**
     * Constructor.
     *
     * @param Hasher $hasher
     * @param string $model
     */
    public function __construct(Hasher $hasher, $model)
    {
        $this->model = $model;
        $this->hasher = $hasher;

        $this->setFallback(new EloquentUserProvider($hasher, $model));
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveById($identifier)
    {
        return $this->fallback->retrieveById($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        return $this->fallback->retrieveByToken($identifier, $token);
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param Authenticatable $user
     * @param string          $token
     *
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $this->fallback->updateRememberToken($user, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Retrieve the LDAP user who is authenticating.
        $user = $this->getResolver()->byCredentials($credentials);

        if ($user instanceof User) {
            // Set the currently authenticating LDAP user.
            $this->user = $user;

            $this->handleDiscoveredWithCredentials($user);

            // Import / locate the local user account.
            return $this->getImporter()
                ->run($user, $this->createModel(), $credentials);
        }

        if ($this->isFallingBack()) {
            return $this->fallback->retrieveByCredentials($credentials);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $model, array $credentials)
    {
        // We'll check if we have an LDAP user, and then make sure
        // they pass authentication before going further.
        if (
            $this->user instanceof User &&
            $this->getResolver()->authenticate($this->user, $credentials)
        ) {
            $this->handleAuthenticatedWithCredentials($this->user, $model);

            // Here we will perform authorization on the LDAP user. If all
            // validation rules pass, we will allow the authentication
            // attempt. Otherwise, it is automatically rejected.
            if ($this->passesValidation($model)) {
                // All of our validation rules have passed and we can
                // finally save the model in case of changes.
                $model->save();

                return true;
            }
        }

        if ($this->isFallingBack() && $model->exists) {
            // If the user exists in our local database already and fallback is
            // enabled, we'll perform standard eloquent authentication.
            return $this->fallback->validateCredentials($model, $credentials);
        }

        return false;
    }

    /**
     * Set the fallback user provider.
     *
     * @param UserProvider $provider
     *
     * @return void
     */
    public function setFallback(UserProvider $provider)
    {
        $this->fallback = $provider;
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Perform all missing method calls on the underlying EloquentUserProvider fallback.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->fallback, $name], $arguments);
    }

    /**
     * Determines if the model passes validation.
     *
     * @param Authenticatable $model
     *
     * @return bool
     */
    protected function passesValidation(Authenticatable $model)
    {
        return $this->newValidator($this->getRules($this->user, $model))
            ->passes();
    }

    /**
     * Determines if login fallback is enabled.
     *
     * @return bool
     */
    protected function isFallingBack()
    {
        return config('adldap_auth.login_fallback', false);
    }
}
