<?php

namespace Adldap\Laravel\Listeners;

use Illuminate\Support\Facades\Log;
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
        Log::info("User '{$event->user->getCommonName()}' has failed LDAP authentication.");
    }
}
