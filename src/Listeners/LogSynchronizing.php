<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\Synchronizing;

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
        info("User '{$event->user->getCommonName()}' is being synchronized.");
    }
}
