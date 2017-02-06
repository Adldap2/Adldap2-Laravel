<?php

namespace Adldap\Laravel\Auth;

use Adldap\Laravel\Traits\UsesAdldap;
use Adldap\Laravel\Traits\DispatchesAuthEvents;
use Illuminate\Contracts\Auth\UserProvider;

abstract class Provider implements UserProvider
{
    use UsesAdldap, DispatchesAuthEvents;
}
