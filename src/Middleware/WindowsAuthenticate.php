<?php

namespace Adldap\Laravel\Middleware;

use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Auth\NoDatabaseUserProvider;
use Adldap\Models\ModelNotFoundException;
use Closure;
use Adldap\Models\User;
use Adldap\Laravel\Events\AuthenticatedWithWindows;
use Adldap\Laravel\Traits\ImportsUsers;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class WindowsAuthenticate
{
    use ImportsUsers;

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
                    // Manually log the user in.
                    $this->auth->login($user);
                }
            }
        }

        return $this->returnNextRequest($request, $next);
    }

    /**
     * Returns the next request.
     *
     * This method exists to be overridden.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function returnNextRequest(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Returns a new auth model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $model = $this->auth->getProvider()->getModel();

        return new $model();
    }

    /**
     * Handle the authenticated user model.
     *
     * This method exists to be overridden.
     *
     * @param User       $user
     * @param Model|null $model
     *
     * @return void
     */
    protected function handleAuthenticatedUser(User $user, Model $model = null)
    {
        Event::fire(new AuthenticatedWithWindows($user, $model));
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
            // Find the user in AD.
            $user = $this->newAdldapUserQuery()
                ->where([$key => $username])
                ->firstOrFail();

            if ($provider instanceof NoDatabaseUserProvider) {
                $this->handleAuthenticatedUser($user);

                return $user;
            } elseif ($provider instanceof DatabaseUserProvider) {
                // Retrieve the Eloquent user model from our AD user instance.
                // We'll assign the user a random password since we don't
                // have access to it through SSO auth.
                $model = $this->getModelFromAdldap($user, str_random());

                // Save model in case of changes.
                $model->save();

                $this->handleAuthenticatedUser($user, $model);

                return $model;
            }
        } catch (ModelNotFoundException $e) {
            //
        }
    }

    /**
     * Returns the windows authentication attribute.
     *
     * @return string
     */
    protected function getWindowsAuthAttribute()
    {
        return config('adldap_auth.windows_auth_attribute', [$this->getSchema()->accountName() => 'AUTH_USER']);
    }
}
