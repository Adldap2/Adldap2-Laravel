<?php

namespace Adldap\Laravel\Auth;

use Adldap\Models\User;
use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Events\Imported;
use Illuminate\Support\Facades\Bus;
use Adldap\Laravel\Facades\Resolver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Adldap\Laravel\Commands\SyncPassword;
use Adldap\Laravel\Traits\ValidatesUsers;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Adldap\Laravel\Events\AuthenticationRejected;
use Adldap\Laravel\Events\AuthenticationSuccessful;
use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Adldap\Laravel\Events\AuthenticatedWithCredentials;

class DatabaseUserProvider extends EloquentUserProvider
{
    use ValidatesUsers;

    /**
     * The currently authenticated LDAP user.
     *
     * @var User|null
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        // Retrieve the LDAP user who is authenticating.
        $user = Resolver::byCredentials($credentials);

        if ($user instanceof User) {
            // Set the currently authenticating LDAP user.
            $this->user = $user;

            Event::dispatch(new DiscoveredWithCredentials($user));

            // Import / locate the local user account.
            return Bus::dispatch(
                new Import($user, $this->createModel())
            );
        }

        if ($this->isFallingBack()) {
            return parent::retrieveByCredentials($credentials);
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
            if (Resolver::authenticate($this->user, $credentials)) {
                Event::dispatch(new AuthenticatedWithCredentials($this->user, $model));

                // Here we will perform authorization on the LDAP user. If all
                // validation rules pass, we will allow the authentication
                // attempt. Otherwise, it is automatically rejected.
                if ($this->passesValidation($this->user, $model)) {
                    // Here we can now synchronize / set the users password since
                    // they have successfully passed authentication
                    // and our validation rules.
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

                Event::dispatch(new AuthenticationRejected($this->user, $model));
            }

            // LDAP Authentication failed.
            return false;
        }

        if ($this->isFallingBack() && $model->exists) {
            // If the user exists in our local database already and fallback is
            // enabled, we'll perform standard eloquent authentication.
            return parent::validateCredentials($model, $credentials);
        }

        return false;
    }

    /**
     * Determines if login fallback is enabled.
     *
     * @return bool
     */
    protected function isFallingBack() : bool
    {
        return Config::get('ldap_auth.login_fallback', false);
    }
}
