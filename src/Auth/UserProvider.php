<?php

namespace Adldap\Laravel\Auth;

use Adldap\Laravel\Traits\ValidatesUsers;
use Illuminate\Contracts\Auth\UserProvider as UserProviderContract;

abstract class UserProvider implements UserProviderContract
{
    use ValidatesUsers;
}
