<?php

namespace Adldap\Laravel\Listeners;

use Adldap\Laravel\Events\AuthenticatedWithCredentials;

class LogCredentialAuth
{
    /**
     * Handle the event.
     *
     * @param AuthenticatedWithCredentials $event
     *
     * @return void
     */
    public function handle(AuthenticatedWithCredentials $event)
    {
        $name = $event->user->getCommonName();

        if ($model = $event->model) {
            info("User {$name} has successfully authenticated with the model ID: {$model->getKey()}");
        } else {
            info("User {$name} has successfully authenticated.");
        }
    }
}
