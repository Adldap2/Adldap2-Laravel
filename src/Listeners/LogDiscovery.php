<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\DiscoveredWithCredentials;

class LogDiscovery
{
    /**
     * Handle the event.
     *
     * @param DiscoveredWithCredentials $event
     *
     * @return void
     */
    public function handle(DiscoveredWithCredentials $event)
    {
        info("User '{$event->user->getCommonName()}' has been successfully found for authentication.");
    }
}
