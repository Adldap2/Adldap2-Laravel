<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\Laravel\Traits\HasLdapUser;
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

        $this->setFallback(
            new EloquentUserProvider($hasher, $model)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveById($identifier)
    {
        $model = $this->fallback->retrieveById($identifier);

        if ($model && $this->isBindingUserToModel($model)) {
            $model->setLdapUser($this->getResolver()->byModel($model));
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        $model = $this->fallback->retrieveByToken($identifier, $token);

        if ($model && $this->isBindingUserToModel($model)) {
            $model->setLdapUser($this->getResolver()->byModel($model));
        }

        return $model;
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
        if ($this->user instanceof User) {
            // If an LDAP user was discovered, we can go
            // ahead and try to authenticate them.
            if ($this->getResolver()->authenticate($this->user, $credentials)) {
                $this->handleAuthenticatedWithCredentials($this->user, $model);

                // Here we will perform authorization on the LDAP user. If all
                // validation rules pass, we will allow the authentication
                // attempt. Otherwise, it is automatically rejected.
                if ($this->newValidator($this->getRules($this->user, $model))->passes()) {
                    $password = null;
                    
                    // We'll check if we've been given a password and that
                    // syncing password is enabled. Otherwise we'll
                    // use a random 16 character string.
                    if ($this->isSyncingPasswords()) {
                        $password = $credentials['password'];
                    } else if (is_null($model->password) || empty($model->password)) {
                        $password = str_random();
                    }

                    // If the model has a set mutator for the password then we'll
                    // assume that we're using a custom encryption method for
                    // passwords. Otherwise we'll bcrypt it normally.
                    if(! is_null($password)) {
                        $model->password = $model->hasSetMutator('password') ?
                            $password : bcrypt($password);
                    }

                    // All of our validation rules have passed and we can
                    // finally save the model in case of changes.
                    $model->save();

                    // If binding to the eloquent model is configured, we
                    // need to make sure it's available during the
                    // same authentication request.
                    if ($this->isBindingUserToModel($model)) {
                        $model->setLdapUser($this->user);
                    }

                    return true;
                }
            }

            // LDAP authentication failure.
            return false;
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
     * Binds the LDAP User instance to the Eloquent model.
     *
     * @param Authenticatable $model
     *
     * @return bool
     */
    protected function isBindingUserToModel(Authenticatable $model)
    {
        return array_key_exists(
            HasLdapUser::class,
            class_uses_recursive(get_class($model))
        );
    }

    /**
     * Determines if passwords are being syncronized.
     *
     * @return bool
     */
    public function isSyncingPasswords()
    {
        return config('adldap_auth.password_sync', true);
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
