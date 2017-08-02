<?php

namespace Adldap\Laravel\Middleware;

use Closure;
use Adldap\Models\User;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Commands\Import;
use Adldap\Laravel\Commands\SyncPassword;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Laravel\Events\AuthenticatedWithWindows;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

class WindowsAuthenticate
{
    /**
     * The authenticator implementation.
     *
     * @var \Illuminate\Contracts\Auth\Guard
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
        if (!$this->auth->check()) {
            // Retrieve the SSO login attribute.
            $auth = $this->attribute();

            // Retrieve the SSO input key.
            $key = key($auth);

            // Handle Windows Authentication.
            if ($account = $this->retrieveAccountFromServer($request, $auth[$key])) {
                // Username's may be prefixed with their domain,
                // we just need their account name.
                $username = explode('\\', $account);

                if (count($username) === 2) {
                    list($domain, $username) = $username;
                } else {
                    $username = $username[key($username)];
                }

                if ($user = $this->retrieveAuthenticatedUser($key, $username)) {
                    $this->auth->login($user, $remember = true);
                }
            }
        }

        return $next($request);
    }

    /**
     * Retrieves the users SSO account name from our server.
     *
     * @param Request $request
     * @param string  $key
     *
     * @return string
     */
    public function retrieveAccountFromServer(Request $request, $key)
    {
        return utf8_encode($request->server($key));
    }

    /**
     * Returns the authenticatable user instance if found.
     *
     * @param string $key
     * @param string $username
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function retrieveAuthenticatedUser($key, $username)
    {
        $provider = $this->auth->getProvider();

        // Find the user in AD.
        if ($user = Resolver::query()->where([$key => $username])->first()) {
            if ($provider instanceof NoDatabaseUserProvider) {
                Event::fire(new AuthenticatedWithWindows($user));

                return $user;
            } elseif ($provider instanceof DatabaseUserProvider) {
                $credentials = $this->makeCredentials($user);

                // Here we'll import the AD user. If the user already exists in
                // our local database, it will be returned from the importer.
                $model = Bus::dispatch(
                    new Import($user, $this->model(), $credentials)
                );

                // We'll sync / set the users password after
                // our model has been synchronized.
                Bus::dispatch(new SyncPassword($model));

                // We also want to save the returned model in case it doesn't
                // exist yet, or there are changes to be synced.
                $model->save();

                Event::fire(new AuthenticatedWithWindows($user, $model));

                return $model;
            }
        }
    }

    /**
     * Returns a credentials array to be used in the import command.
     *
     * @param User $user
     *
     * @return array
     */
    protected function makeCredentials(User $user)
    {
        return [
            Resolver::getEloquentUsername() => $user->getFirstAttribute(Resolver::getLdapUsername()),
        ];
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
     * Returns the windows authentication attribute.
     *
     * @return string
     */
    protected function attribute()
    {
        return config('adldap_auth.windows_auth_attribute', ['samaccountname' => 'AUTH_USER']);
    }
}
