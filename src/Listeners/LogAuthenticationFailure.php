<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticationFailed;

class LogAuthenticationFailure
{
    /**
     * Handle the event.
     *
     * @param AuthenticationFailed $event
     *
     * @return void
     */
    public function handle(AuthenticationFailed $event)
    {
        info("User '{$event->user->getCommonName()}' has failed LDAP authentication.");
    }
}
