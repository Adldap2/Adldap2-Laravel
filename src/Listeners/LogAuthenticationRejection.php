<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticationRejected;

class LogAuthenticationRejection
{
    /**
     * Constructor.
     *
     * @param AuthenticationRejected $event
     */
    public function __construct(AuthenticationRejected $event)
    {
        info("User '{$event->user->getCommonName()}' has failed validation. They have been denied authentication.");
    }
}
