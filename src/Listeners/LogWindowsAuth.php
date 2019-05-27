<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticatedWithWindows;
use Illuminate\Support\Facades\Log;

class LogWindowsAuth
{
    /**
     * Handle the event.
     *
     * @param AuthenticatedWithWindows $event
     *
     * @return void
     */
    public function handle(AuthenticatedWithWindows $event)
    {
        Log::info("User '{$event->user->getCommonName()}' has successfully authenticated via NTLM.");
    }
}
