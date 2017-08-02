<?php

namespace Adldap\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Adldap\Laravel\Resolvers\ResolverInterface;

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
