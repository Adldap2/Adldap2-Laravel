<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticationRejected;
use Illuminate\Support\Facades\Log;

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
