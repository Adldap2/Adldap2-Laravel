<?php

namespace Adldap\Laravel\Listeners;

use Illuminate\Support\Facades\Log;
use Adldap\Laravel\Events\Synchronized;

class LogSynchronized
{
    /**
     * Handle the event.
     *
     * @param Synchronized $event
     *
     * @return void
     */
    public function handle(Synchronized $event)
    {
        Log::info("User '{$event->user->getCommonName()}' has been successfully synchronized.");
    }
}
