<?php

namespace Adldap\Laravel\Middleware;

use Adldap\Laravel\Traits\ImportsUsers;
use Adldap\Models\User;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

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
        // If the user is already logged in, we don't need to reauthenticate.
        if (!$this->auth->check()) {
            // Retrieve the SSO login attribute.
            $auth = $this->getWindowsAuthAttribute();

            // Retrieve the SSO input key.
            $key = key($auth);

            // Handle Windows Authentication.
            if ($account = $request->server($auth[$key])) {
                // Usernames may be prefixed with their domain,
                // we just need their account name.
                $username = explode('\\', $account);

                if (count($username) === 2) {
                    list($domain, $username) = $username;
                } else {
                    $username = $username[key($username)];
                }

                // Create a new user LDAP user query.
                $query = $this->newAdldapUserQuery();

                // Filter the query by the username attribute
                $query->whereEquals($key, $username);

                // Retrieve the first user result
                $user = $query->first();

                if ($user instanceof User) {
                    $model = $this->getModelFromAdldap($user, str_random());

                    if ($model instanceof Model) {
                        // Double check user instance before logging them in.
                        $this->auth->login($model);
                    }
                }
            }
        }

        return $this->returnNextRequest($request, $next);
    }

    /**
     * Returns the next request.
     *
     * This method exists for override ability.
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
     * Returns the windows authentication attribute.
     *
     * @return string
     */
    protected function getWindowsAuthAttribute()
    {
        return Config::get('adldap_auth.windows_auth_attribute', [$this->getSchema()->accountName() => 'AUTH_USER']);
    }
}
