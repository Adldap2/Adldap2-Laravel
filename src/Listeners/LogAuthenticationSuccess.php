<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticationSuccessful;

class LogAuthenticationSuccess
{
    /**
     * Handle the event.
     *
     * @param AuthenticationSuccessful $event
     *
     * @return void
     */
    public function handle(AuthenticationSuccessful $event)
    {
        info("User '{$event->user->getCommonName()}' has been successfully logged in.");
    }
}
