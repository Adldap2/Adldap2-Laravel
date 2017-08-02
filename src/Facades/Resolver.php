<?php

namespace Adldap\Laravel\Facades;

use Adldap\Laravel\Auth\ResolverInterface;
use Illuminate\Support\Facades\Facade;

class Resolver extends Facade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor()
    {
        return ResolverInterface::class;
    }
}
