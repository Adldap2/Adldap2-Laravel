<?php

namespace Adldap\Laravel\Middleware;

use Closure;
use Adldap\Models\User;
use Illuminate\Http\Request;
use Adldap\Laravel\Commands\Import;
use Illuminate\Support\Facades\Bus;
use Adldap\Laravel\Facades\Resolver;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Adldap\Laravel\Commands\SyncPassword;
use Adldap\Laravel\Traits\ValidatesUsers;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Events\AuthenticatedWithWindows;

class WindowsAuthenticate
{
    use ValidatesUsers;

    /**
     * The authenticator implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Constructor.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $this->auth->check()) {
            // Retrieve the users account name from the request.
            if ($account = $this->account($request)) {
                // Retrieve the users username from their account name.
                $username = $this->username($account);

                // Finally, retrieve the users authenticatable model and log them in.
                if ($user = $this->retrieveAuthenticatedUser($username)) {
                    $this->auth->login($user, $remember = true);
                }
            }
        }

        return $next($request);
    }

    /**
     * Returns the authenticatable user instance if found.
     *
     * @param string $username
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function retrieveAuthenticatedUser($username)
    {
        // Find the user in LDAP.
        if ($user = $this->resolveUserByUsername($username)) {
            $model = null;

            // If we are using the DatabaseUserProvider, we must locate or import
            // the users model that is currently authenticated with SSO.
            if ($this->auth->getProvider() instanceof DatabaseUserProvider) {
                // Here we will import the LDAP user. If the user already exists in
                // our local database, it will be returned from the importer.
                $model = Bus::dispatch(
                    new Import($user, $this->model())
                );
            }

            // Here we will validate that the authenticating user
            // passes our LDAP authentication rules in place.
            if ($this->passesValidation($user, $model)) {
                if ($model) {
                    // We will sync / set the users password after
                    // our model has been synchronized.
                    Bus::dispatch(new SyncPassword($model));

                    // We also want to save the model in case it doesn't
                    // exist yet, or there are changes to be synced.
                    $model->save();
                }

                $this->fireAuthenticatedEvent($user, $model);

                return $model ? $model : $user;
            }
        }
    }

    /**
     * Fires the windows authentication event.
     *
     * @param User       $user
     * @param mixed|null $model
     *
     * @return void
     */
    protected function fireAuthenticatedEvent(User $user, $model = null)
    {
        Event::dispatch(new AuthenticatedWithWindows($user, $model));
    }

    /**
     * Retrieves an LDAP user by their username.
     *
     * @param string $username
     *
     * @return mixed
     */
    protected function resolveUserByUsername($username)
    {
        return Resolver::query()->whereEquals($this->discover(), $username)->first();
    }

    /**
     * Returns the configured authentication model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function model()
    {
        return $this->auth->getProvider()->createModel();
    }

    /**
     * Retrieves the users SSO account name from our server.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function account(Request $request)
    {
        return utf8_encode($request->server($this->key()));
    }

    /**
     * Retrieves the users username from their full account name.
     *
     * @param string $account
     *
     * @return string
     */
    protected function username($account)
    {
        // Username's may be prefixed with their domain,
        // we just need their account name.
        $username = explode('\\', $account);

        if (count($username) === 2) {
            [$domain, $username] = $username;
        } else {
            $username = $username[key($username)];
        }

        return $username;
    }

    /**
     * Returns the configured key to use for retrieving
     * the username from the server global variable.
     *
     * @return string
     */
    protected function key()
    {
        return Config::get('ldap_auth.identifiers.windows.server_key', 'AUTH_USER');
    }

    /**
     * Returns the attribute to discover users by.
     *
     * @return string
     */
    protected function discover()
    {
        return Config::get('ldap_auth.identifiers.windows.locate_users_by', 'samaccountname');
    }
}
