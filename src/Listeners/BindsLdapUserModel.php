<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Auth\Provider;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Traits\HasLdapUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;

class BindsLdapUserModel
{
    /**
     * Binds the LDAP user record to their model.
     *
     * @param mixed $event
     *
     * @return void
     */
    public function handle($event)
    {
        // Before we bind the users LDAP model, we will verify they are using the
        // Adldap authentication provider, the required trait, and the
        // users LDAP property has not already been set.
        if (
            $this->isUsingAdldapProvider()
            && $this->canBind($event->user)
            && is_null($event->user->ldap)
        ) {
            $event->user->setLdapUser(
                Resolver::byModel($event->user)
            );
        }
    }

    /**
     * Determines if the Auth Provider is an instance of the Adldap Provider.
     *
     * @return bool
     */
    protected function isUsingAdldapProvider() : bool
    {
        return Auth::getProvider() instanceof Provider;
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
        return array_key_exists(HasLdapUser::class, class_uses_recursive($user));
    }
}
