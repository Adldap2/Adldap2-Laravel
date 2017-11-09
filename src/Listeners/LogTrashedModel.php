<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticatedModelTrashed;

class LogTrashedModel
{
    /**
     * Handle the event.
     *
     * @param AuthenticatedModelTrashed $event
     *
     * @return void
     */
    public function handle(AuthenticatedModelTrashed $event)
    {
        info("User '{$event->user->getCommonName()}' was denied authentication because their model has been soft-deleted.");
    }
}
