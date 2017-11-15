<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\Authenticated;

class LogAuthenticated
{
    /**
     * Handle the event.
     *
     * @param Authenticated $event
     *
     * @return void
     */
    public function handle(Authenticated $event)
    {
        info("User '{$event->user->getCommonName()}' has successfully passed LDAP authentication.");
    }
}
