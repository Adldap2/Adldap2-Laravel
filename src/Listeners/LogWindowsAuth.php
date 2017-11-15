<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticatedWithWindows;

class LogWindowsAuth
{
    /**
     * Handle the event.
     *
     * @param AuthenticatedWithWindows $event
     *
     * @return void
     */
    public function handle(AuthenticatedWithWindows $event)
    {
        info("User '{$event->user->getCommonName()}' has successfully authenticated via NTLM.");
    }
}
