<?php

namespace Adldap\Laravel\Middleware;

use Closure;
use Adldap\Models\ModelNotFoundException;
use Adldap\Laravel\Traits\UsesAdldap;
use Adldap\Laravel\Traits\DispatchesAuthEvents;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;

class WindowsAuthenticate
{
    use UsesAdldap, DispatchesAuthEvents;

    /**
     * The authenticator implementation.
     *
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
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
            $auth = $this->getWindowsAuthAttribute();

            // Retrieve the SSO input key.
            $key = key($auth);

            // Handle Windows Authentication.
            if ($account = $request->server($auth[$key])) {
                // Username's may be prefixed with their domain,
                // we just need their account name.
                $username = explode('\\', $account);

                if (count($username) === 2) {
                    list($domain, $username) = $username;
                } else {
                    $username = $username[key($username)];
                }

                if ($user = $this->retrieveAuthenticatedUser($key, $username)) {
                    $this->auth->login($user);
                }
            }
        }

        return $next($request);
    }

    /**
     * Returns the authenticatable user instance.
     *
     * @param string $key
     * @param string $username
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function retrieveAuthenticatedUser($key, $username)
    {
        $provider = $this->auth->getProvider();

        try {
            $resolver = $this->getResolver();

            // Find the user in AD.
            $user = $resolver->query()->where([$key => $username])->firstOrFail();

            if ($provider instanceof NoDatabaseUserProvider) {
                $this->handleAuthenticatedWithWindows($user);

                return $user;
            } elseif ($provider instanceof DatabaseUserProvider) {
                $credentials = [
                    $resolver->getEloquentUsername() => $user->getFirstAttribute($resolver->getLdapUsername()),
                ];

                // Here we'll import the AD user. If the user already exists in
                // our local database, it will be returned from the importer.
                $model = $this->getImporter()->run($user, $this->getModel(), $credentials);

                // We'll assign a random password for the authenticating user.
                $password = str_random();

                // Set the models password.
                $model->password = $model->hasSetMutator('password') ?
                    $password : bcrypt($password);

                // We also want to save the returned model in case it doesn't
                // exist yet, or there are changes to be synced.
                $model->save();

                $this->handleAuthenticatedWithWindows($user, $model);

                return $model;
            }
        } catch (ModelNotFoundException $e) {
            //
        }
    }

    /**
     * Returns the configured authentication model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModel()
    {
        return auth()->getProvider()->createModel();
    }

    /**
     * Returns the windows authentication attribute.
     *
     * @return string
     */
    protected function getWindowsAuthAttribute()
    {
        return config('adldap_auth.windows_auth_attribute', ['samaccountname' => 'AUTH_USER']);
    }
}
