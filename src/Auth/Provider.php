<?php

namespace Adldap\Laravel\Auth;

use Adldap\Laravel\Traits\ValidatesUsers;
use Illuminate\Contracts\Auth\UserProvider;

abstract class Provider implements UserProvider
{
    use ValidatesUsers;
}
