<?php

namespace Adldap\Laravel\Traits;

use Adldap\Models\User;
use Adldap\Laravel\Events\AuthenticatedWithWindows;
use Adldap\Laravel\Events\DiscoveredWithCredentials;
use Adldap\Laravel\Events\AuthenticatedWithCredentials;
use Illuminate\Contracts\Auth\Authenticatable;

trait DispatchesAuthEvents
{
    /**
     * Dispatches an authentication event when users login by their credentials.
     *
     * @param User                 $user
     * @param Authenticatable|null $model
     *
     * @return void
     */
    public function handleAuthenticatedWithCredentials(User $user, Authenticatable $model = null)
    {
        event(new AuthenticatedWithCredentials($user, $model));
    }

    /**
     * Dispatches an event when a user has logged in via the WindowsAuthenticate middleware.
     *
     * @param User                 $user
     * @param Authenticatable|null $model
     *
     * @return void
     */
    protected function handleAuthenticatedWithWindows(User $user, Authenticatable $model = null)
    {
        event(new AuthenticatedWithWindows($user, $model));
    }

    /**
     * Dispatches an event when a user has been discovered by the their credentials.
     *
     * @param User $user
     */
    protected function handleDiscoveredWithCredentials(User $user)
    {
        event(new DiscoveredWithCredentials($user));
    }
}
