<?php

namespace Adldap\Laravel\Listeners;

use Illuminate\Support\Facades\Log;
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
        Log::info("User '{$event->user->getCommonName()}' was denied authentication because their model has been soft-deleted.");
    }
}
