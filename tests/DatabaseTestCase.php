<?php

namespace Adldap\Laravel\Tests;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Create the users table for testing
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('objectguid')->unique()->nullable();
            $table->string('password', 60);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Hash::setRounds(4);
    }
}
