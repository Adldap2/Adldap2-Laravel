<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\Authenticated;
use Illuminate\Support\Facades\Log;

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
        Log::info("User '{$event->user->getCommonName()}' has successfully passed LDAP authentication.");
    }
}
