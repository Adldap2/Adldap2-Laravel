<?php

namespace Adldap\Laravel\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Adldap\Laravel\Events\Authenticating;

class LogAuthentication
{
    /**
     * Handle the event.
     *
     * @param Authenticating $event
     *
     * @return void
     */
    public function handle(Authenticating $event)
    {
        $username = $this->getPrefix().$event->username.$this->getSuffix();

        Log::info("User '{$event->user->getCommonName()}' is authenticating with username: '{$username}'");
    }

    /**
     * Returns the account prefix that is applied to username's.
     *
     * @return string|null
     */
    protected function getPrefix()
    {
        return Config::get("{$this->getConfigSettingsPath()}.account_prefix");
    }

    /**
     * Returns the account suffix that is applied to username's.
     *
     * @return string|null
     */
    protected function getSuffix()
    {
        return Config::get("{$this->getConfigSettingsPath()}.account_suffix");
    }

    /**
     * Returns the current connections configuration path.
     *
     * @return string
     */
    protected function getConfigSettingsPath()
    {
        $connection = Config::get('ldap_auth.connection');

        return "ldap.connections.$connection.settings";
    }
}
