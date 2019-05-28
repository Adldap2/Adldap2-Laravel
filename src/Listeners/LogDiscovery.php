<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Illuminate\Support\Facades\Log;

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
        Log::info("User '{$event->user->getCommonName()}' has been successfully found for authentication.");
    }
}
