<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Traits\HasLdapUser;
use Adldap\Laravel\Auth\DatabaseUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;

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
        // Before we bind the users LDAP model, we will verify they are using the Adldap
        // authentication provider, the required trait, the users LDAP property has
        // not already been set, and we have located an LDAP user to bind.
        if (
            $this->isUsingAdldapProvider($event->guard)
            && $this->canBind($event->user)
            && $user = Resolver::byModel($event->user)
        ) {
            $event->user->setLdapUser($user);
        }
    }

    /**
     * Determines if the Auth Provider is an instance of the Adldap Provider.
     *
     * @param string $guard
     *
     * @return bool
     */
    protected function isUsingAdldapProvider($guard) : bool
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
    protected function canBind(Authenticatable $user) : bool
    {
        return array_key_exists(HasLdapUser::class, class_uses_recursive($user)) && is_null($user->ldap);
    }
}
