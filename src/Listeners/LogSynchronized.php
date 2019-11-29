<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\Synchronized;
use Illuminate\Support\Facades\Log;

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
