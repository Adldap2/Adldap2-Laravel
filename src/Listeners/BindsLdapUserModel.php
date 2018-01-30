<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Auth\Provider;
use Adldap\Laravel\Facades\Resolver;
use Adldap\Laravel\Traits\HasLdapUser;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class BindsLdapUserModel
{
    /**
     * Binds the LDAP user record to their model.
     *
     * @param Authenticated $event
     *
     * @return void
     */
    public function handle(Authenticated $event)
    {
        if ($this->isUsingAdldapProvider() && $this->canBind($event->user)) {
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
        $traits = class_uses_recursive($user);

        return array_key_exists(HasLdapUser::class, $traits);
    }
}
