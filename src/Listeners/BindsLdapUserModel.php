<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Traits\HasLdapUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Authenticated;

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
        $traits = class_uses_recursive($event->user);

        if (array_key_exists(HasLdapUser::class, $traits)) {
            $event->user->setLdapUser(
                Auth::getProvider()->getResolver()->byModel($event->user)
            );
        }
    }
}
