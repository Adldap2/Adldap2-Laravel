<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Auth\DatabaseUserProvider;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Traits\HasLdapUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class BindsLdapUserModel
{
    /**
     * Binds the LDAP user record to their model.
     *
     * @param \Illuminate\Auth\Events\Login|\Illuminate\Auth\Events\Authenticated $event
     *
     * @return void
     */
    public function handle($event)
    {
        $guard = null;

        // We'll retrieve the auth guard if available.
        if (property_exists($event, 'guard')) {
            $guard = $event->guard;
        }

        // Before we bind the users LDAP model, we will verify they are using
        // the Adldap authentication provider, and the required trait.
        if ($this->isUsingAdldapProvider($guard) && $this->canBind($event->user)) {
            $event->user->setLdapUser(
                Resolver::byModel($event->user)
            );
        }
    }

    /**
     * Determines if the Auth Provider is an instance of the Adldap Provider.
     *
     * @param string|null $guard
     *
     * @return bool
     */
    protected function isUsingAdldapProvider($guard = null): bool
    {
        return Auth::guard($guard)->getProvider() instanceof DatabaseUserProvider;
    }

    /**
     * Determines if we're able to bind to the user.
     *
     * @param Authenticatable $user
     *
     * @return bool
     */
    protected function canBind(Authenticatable $user): bool
    {
        return array_key_exists(HasLdapUser::class, class_uses_recursive($user)) && is_null($user->ldap);
    }
}
