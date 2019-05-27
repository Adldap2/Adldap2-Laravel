<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\Synchronizing;
use Illuminate\Support\Facades\Log;

class LogSynchronizing
{
    /**
     * Handle the event.
     *
     * @param Synchronizing $event
     *
     * @return void
     */
    public function handle(Synchronizing $event)
    {
        Log::info("User '{$event->user->getCommonName()}' is being synchronized.");
    }
}
