<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\AdldapServiceProvider;
use Adldap\Laravel\Exceptions\ConfigurationMissingException;
use Illuminate\Support\Facades\App;

class AdldapTest extends FunctionalTestCase
{
    public function testConfigurationNotFoundException()
    {
        $this->setExpectedException(ConfigurationMissingException::class);

        App::make('adldap');
    }

    public function testRegistration()
    {
        $this->assertTrue(app()->register(AdldapServiceProvider::class));
    }
}
