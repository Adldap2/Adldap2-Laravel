<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\AdldapServiceProvider;
use Adldap\Laravel\Exceptions\ConfigurationMissingException;
use Illuminate\Support\Facades\App;

class AdldapTest extends FunctionalTestCase
{
    public function testConfigurationNotFoundException()
    {
        $this->setExpectedException('Adldap\Laravel\Exceptions\ConfigurationMissingException');

        App::make('adldap');
    }

    public function testRegistration()
    {
        $this->assertTrue(app()->register('Adldap\Laravel\AdldapServiceProvider'));
    }
}
