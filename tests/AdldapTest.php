<?php

namespace Adldap\Laravel\Tests;

use Adldap\Laravel\Exceptions\ConfigurationMissingException;
use Illuminate\Support\Facades\App;
use Adldap\Laravel\Facades\Adldap as AdldapFacade;
use Adldap\Adldap;

class AdldapTest extends FunctionalTestCase
{
    public function testConfigurationNotFoundException()
    {
        $this->setExpectedException(ConfigurationMissingException::class);

        App::make('adldap');
    }

    public function testIsBound()
    {
        
    }
}
