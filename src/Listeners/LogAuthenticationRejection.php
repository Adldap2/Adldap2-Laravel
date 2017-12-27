<?php

namespace Adldap\Laravel\Listeners;

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
        info("User '{$event->user->getCommonName()}' has failed validation. They have been denied authentication.");
    }
}
