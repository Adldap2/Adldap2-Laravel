<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\Importing;

class LogImport
{
    /**
     * Handle the event.
     *
     * @param Importing $event
     *
     * @return void
     */
    public function handle(Importing $event)
    {
        info("User '{$event->user->getCommonName()}' is being imported.");
    }
}
