<?php

namespace Adldap\Laravel\Listeners;

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
        info("User '{$event->user->getCommonName()}' has been successfully synchronized.");
    }
}
