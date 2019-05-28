<?php

namespace Adldap\Laravel\Listeners;

use Illuminate\Support\Facades\Log;
use Adldap\Laravel\Events\AuthenticatedWithWindows;

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
