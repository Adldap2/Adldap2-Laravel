<?php

namespace Adldap\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class Adldap extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'adldap';
    }
}
