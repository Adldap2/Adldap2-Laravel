<?php

namespace Adldap\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Adldap
 * @package Adldap\Laravel\Facades
 * @mixin \Adldap\Connections\Provider
 */
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
