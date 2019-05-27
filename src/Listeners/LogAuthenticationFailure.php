<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticationFailed;
use Illuminate\Support\Facades\Log;

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
