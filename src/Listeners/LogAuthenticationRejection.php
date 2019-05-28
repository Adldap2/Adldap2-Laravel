<?php

namespace Adldap\Laravel\Listeners;

use Illuminate\Support\Facades\Log;
use Adldap\Laravel\Events\AuthenticationRejected;

class LogAuthenticationRejection
{
    /**
     * Handle the event.
     *
     * @param AuthenticationRejected $event
     */
    public function handle(AuthenticationRejected $event)
    {
        Log::info("User '{$event->user->getCommonName()}' has failed validation. They have been denied authentication.");
    }
}
